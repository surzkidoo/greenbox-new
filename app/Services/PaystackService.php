<?php
namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class PaystackService
{
    protected $httpClient;
    protected $secretKey;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
    }

    // Function to create a payment link or charge customer
    public function createPayment($amount, $email, $orderId)
    {
        try {
            $response = $this->httpClient->post('https://api.paystack.co/transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
                'form_params' => [
                    'email' => $email,
                    'amount' => $amount * 100, // Convert to Kobo (Paystack expects the amount in kobo)
                    'order_id' => $orderId,
                    'callback_url' => route('payment.callback'),
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());

            return $data;
        } catch (Exception $e) {
            return ['error' => 'Error creating payment: ' . $e->getMessage()];
        }
    }

    // Function to verify payment status
    public function verifyPayment($reference)
    {
        try {
            $response = $this->httpClient->get("https://api.paystack.co/transaction/verify/{$reference}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents());

            return $data;
        } catch (Exception $e) {
            return ['error' => 'Error verifying payment: ' . $e->getMessage()];
        }
    }

    public function getBankList()
    {
        try {
            $response = $this->httpClient->get('https://api.paystack.co/bank', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents());

            return $data;
        } catch (Exception $e) {
            return ['error' => 'Error fetching bank list: ' . $e->getMessage()];
        }
    }

    // Function to create recipient (bank account)
    public function createRecipient($name, $accountNumber, $bankCode, $currency = 'NGN')
    {
        try {
            $response = $this->httpClient->post('https://api.paystack.co/transferrecipient', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
                'json' => [
                    'type' => 'nuban',
                    'name' => $name,
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                    'currency' => $currency,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());

            return $data;
        } catch (Exception $e) {
            return ['error' => 'Error creating recipient: ' . $e->getMessage()];
        }
    }

    // Function to create a transfer (withdrawal)
    public function createTransfer($amount, $recipientAccount)
    {
        try {
            $response = $this->httpClient->post('https://api.paystack.co/transfer', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
                'json' => [
                    'amount' => $amount * 100, // Convert to Kobo
                    'recipient' => $recipientAccount,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());

            return $data;
        } catch (Exception $e) {
            return ['error' => 'Error creating transfer: ' . $e->getMessage()];
        }
    }
}
