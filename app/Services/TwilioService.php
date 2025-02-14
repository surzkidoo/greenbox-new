<?php

namespace App\Services;

use Twilio\Rest\Client;
use GuzzleHttp\Client as GuzzleClient;
use Twilio\Http\GuzzleClient as TwilioGuzzleClient;

class TwilioService
{
    protected $twilio;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        // Create a custom Guzzle HTTP client with SSL verification disabled
        $guzzle = new GuzzleClient([
            'verify' => false, // Disable SSL verification
        ]);

        // Pass the Guzzle client to Twilio
        $httpClient = new TwilioGuzzleClient($guzzle);

        // Initialize Twilio Client
        $this->twilio = new Client($sid, $token, $sid, null, $httpClient);
    }

    public function sendSms($to, $message)
    {

        // check or covert number to +234

        $to = $this->convertToNigerianFormat($to);

        return $this->twilio->messages->create($to, [

            // 'from' => env('TWILIO_PHONE_NUMBER'),
            "messagingServiceSid" => "MG0b1b70b859d53bb8afeaeed959611b2d",
            'body' => $message
        ]);
    }

    public function convertToNigerianFormat($phoneNumber)
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Check if it starts with '0' (local format), and replace it with '+234'
        if (substr($phoneNumber, 0, 1) == '0') {
            $phoneNumber = '+234' . substr($phoneNumber, 1);
        }

        // If the number is already in international format (+234), return as is
        if (substr($phoneNumber, 0, 4) == '+234') {
            return $phoneNumber;
        }

        // If the number doesn't match, return an error or empty string (optional)
        return '';
    }
}
