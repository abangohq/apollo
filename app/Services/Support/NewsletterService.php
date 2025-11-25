<?php

namespace App\Services\Support;

use App\Mail\Newsletter;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\PlunkEmailService;
use Illuminate\Support\Facades\Mail;

class NewsletterService
{

    public function handle($payload)
    {
        $data = $payload['data'];
        
        // Determine the target audience
        switch ($payload['target']) {
            case 'all_users':
                $query = User::query();
                break;
            case 'active_users':
                $query = User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
                ->where('crypto_transactions.created_at', '>', now()->subDays(30));
                break;
            case 'inactive_users':
                $query = User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
                ->where('crypto_transactions.created_at', '<', now()->subDays(30));
                break;
            case 'recent_users':
                $query = User::where('created_at', '>', now()->subDays(30));
                break;
            case 'admin':
                $query = User::where('user_type', 'admin');
                break;
            default:
                throw new \InvalidArgumentException('Invalid target type');
        }

        $emails = $query->pluck('email')->toArray();

        $body = view('mail.newsletter', ['body' => $data['body']])->render();

        $response = (new PlunkEmailService())->createCampaign($data['subject'], $body, $emails);
        
        if ($response && isset($response['id'])) {
            $campaignId = $response['id'];
            (new PlunkEmailService())->sendCampaign($campaignId);
        }else{
            throw new \Exception('Failed to create campaign');
        }
    }


}
