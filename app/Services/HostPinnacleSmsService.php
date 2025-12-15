<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostPinnacleSmsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.host_pinnacle.url', 'https://api.hostpinnacle.com/sms');
        $this->apiKey = config('services.host_pinnacle.api_key');
        $this->senderId = config('services.host_pinnacle.sender_id', 'PARTY');
    }

    /**
     * Send SMS via Host Pinnacle API.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Format phone number (ensure it starts with country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'to' => $formattedPhone,
                'message' => $message,
                'sender_id' => $this->senderId,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $response->json(),
                ];
            }

            Log::error('SMS sending failed', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('SMS service exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to include country code.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If it doesn't start with country code, add 254 for Kenya
        if (!str_starts_with($phone, '254')) {
            // Remove leading 0 if present
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = '254' . $phone;
        }

        return $phone;
    }
}

