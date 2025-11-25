<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PlunkEmailService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.plunk.secretKey');
    }

    public function sendEmail(string $to, string $subject, string $body)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.useplunk.com/v1/send', [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Email error', [$e->getMessage()]);
            return false;
        }
    }

    public function triggerMail(string $event, string $email, array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.useplunk.com/v1/track', [
                'event' => $event,
                'email' => $email,
                'data' => $data,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Email error', [$e->getMessage()]);
            return false;
        }
    }

    public function createCampaign(string $subject, string $body, array $recipients)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.useplunk.com/v1/campaigns', [
                'subject' => $subject,
                'body' => $body,
                'recipients' => $recipients,
                'style' => 'HTML',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Campaign error', [$e->getMessage()]);
            return false;
        }
    }

    public function sendCampaign($id, $delay = 0)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.useplunk.com/v1/campaigns/send', [
                'id' => $id,
                'live' => true,
                'delay' => $delay,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Campaign error', [$e->getMessage()]);
            return false;
        }
    }
}