<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostPinnacleSmsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $userId;
    protected string $password;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.hostpinnacle_sms.url');
        $this->apiKey = config('services.hostpinnacle_sms.api_key');
        $this->userId = config('services.hostpinnacle_sms.user_id');
        $this->password = config('services.hostpinnacle_sms.password');
        $this->senderId = config('services.hostpinnacle_sms.sender_id');
    }

    /**
     * Send SMS via Host Pinnacle API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            Log::info('Attempting to send SMS', [
                'phone' => $formattedPhone,
                'original_phone' => $phoneNumber,
            ]);

            $response = Http::withOptions(['verify' => false]) // For cPanel SSL issues
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'cache-control' => 'no-cache',
                ])
                ->asForm()
                ->post($this->apiUrl, [
                    'userid' => $this->userId,
                    'password' => $this->password,
                    'mobile' => $formattedPhone,
                    'msg' => $message,
                    'senderid' => $this->senderId,
                    'msgType' => 'text',
                    'duplicatecheck' => 'true',
                    'output' => 'json',
                    'sendMethod' => 'quick',
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('SMS sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $result,
                ]);

                return true;
            }

            Log::error('SMS sending failed', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('SMS service exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send OTP SMS
     *
     * @param string $phoneNumber
     * @param string $code
     * @return bool
     */
    public function sendOtp(string $phoneNumber, string $code): bool
    {
        $message = "Your verification code is: {$code}. Valid for 10 minutes. Do not share this code with anyone.";
        
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send bulk SMS to multiple numbers
     *
     * @param array $phoneNumbers
     * @param string $message
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message): array
    {
        $results = [];
        
        foreach ($phoneNumbers as $phone) {
            $results[$phone] = $this->sendSms($phone, $message);
        }
        
        return $results;
    }

    /**
     * Send SMS to a group (using Host Pinnacle group feature)
     *
     * @param string $groupName
     * @param string $message
     * @return bool
     */
    public function sendGroupSms(string $groupName, string $message): bool
    {
        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'cache-control' => 'no-cache',
                ])
                ->asForm()
                ->post($this->apiUrl, [
                    'userid' => $this->userId,
                    'password' => $this->password,
                    'group' => $groupName,
                    'msg' => $message,
                    'senderid' => $this->senderId,
                    'msgType' => 'text',
                    'duplicatecheck' => 'true',
                    'output' => 'json',
                    'sendMethod' => 'group',
                ]);

            if ($response->successful()) {
                Log::info('Group SMS sent successfully', [
                    'group' => $groupName,
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::error('Group SMS sending failed', [
                'group' => $groupName,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Group SMS service exception', [
                'group' => $groupName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format phone number to Kenyan format
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // Handle different formats
        if (str_starts_with($phone, '254')) {
            // Already in correct format: 254712345678
            return $phone;
        } elseif (str_starts_with($phone, '0')) {
            // Format: 0712345678 -> 254712345678
            return '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            // Format: 712345678 -> 254712345678
            return '254' . $phone;
        }

        // Default: assume it needs 254 prefix
        return '254' . $phone;
    }

    /**
     * Validate Kenyan phone number format
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);
        
        // Kenyan numbers are 254 + 9 digits (e.g., 254712345678)
        return preg_match('/^254[17]\d{8}$/', $formatted);
    }
}

