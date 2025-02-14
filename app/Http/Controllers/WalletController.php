<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WalletController extends Controller
{
    // Get wallet for the authenticated user
    public function getWallet(): JsonResponse
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();

        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
        }

        return response()->json(['status' => 'success', 'wallet' => $wallet], 200);
    }


    public function getWalletAdmin(Request $request): JsonResponse
    {
        $query = WalletTransaction::query();

        $query->with('wallet.user');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }


        if ($request->has('transaction')) {
            $query->where('transaction', 'like', '%' . $request->input('transaction') . '%');
        }

        if ($request->has('transaction_id')) {
            $query->where('transaction_id', $request->input('transaction_id'));
        }

        $wallets = $query->orderBy('created_at', 'desc')->get();

        if ($wallets->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No wallets found.'], 404);
        }

        return response()->json(['status' => 'success', 'transactions' => $wallets], 200);

    }

    // Fund wallet using Paystack
    public function fundWallet(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:100',
        ]);

        $user = Auth::user();
        $amount = $request->amount * 100; // Paystack expects amount in kobo

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amount,
            'callback_url' => route('paystack.callback'), //to be change to fronend page
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to initialize payment.'], 500);
        }

        $data = $response->json();

        return response()->json(['status' => 'success', 'authorization_url' => $data['data']['authorization_url']], 200);
    }

    // Paystack callback to update wallet balance
    public function paystackCallback(Request $request): JsonResponse
    {
        $reference = $request->query('reference');

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))->get("https://api.paystack.co/transaction/verify/{$reference}");

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Payment verification failed.'], 500);
        }

        $data = $response->json();

        if ($data['data']['status'] !== 'success') {
            return response()->json(['status' => 'error', 'message' => 'Payment was not successful.'], 400);
        }

        $amount = $data['data']['amount'] / 100; // Convert kobo to naira
        $userId = Auth::id();
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        $oldBalance = $wallet->balance;
        $newBalance = $oldBalance + $amount;

        $wallet->balance = $newBalance;
        $wallet->save();

        WalletTransaction::create([
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'transaction_type' => 1,
            'status' => 'success',
            'date' => Carbon::now(),
            'wallet_id' => $wallet->id,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Wallet funded successfully.', 'balance' => $newBalance], 200);
    }

    // Get wallet transactions for the authenticated user
    public function getWalletTransactions(): JsonResponse
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();

        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
        }

        $transactions = $wallet->transactions()->orderBy('created_at', 'desc')->paginate(50);

        return response()->json(['status' => 'success', 'transactions' => $transactions], 200);
    }
}
