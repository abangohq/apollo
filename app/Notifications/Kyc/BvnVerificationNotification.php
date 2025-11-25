<?php

namespace App\Notifications\Kyc;

use App\Enums\VerificationStatus;
use App\Models\Kyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;

class BvnVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Kyc $kyc)
    {
        $this->queue = 'notifications';
        $this->delay(now()->addMinutes(3));
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'firebase'];
    }

    protected function isKycSuccessful(): bool
    {
        // Always get the latest KYC status from database to avoid stale data
        $latestKyc = Kyc::where('reference', $this->kyc->reference)->first();
        
        if (!$latestKyc) {
            logger('KYC record not found for reference: ' . $this->kyc->reference);
            return false;
        }
        
        $isSuccessful = $latestKyc->status === VerificationStatus::COMPLETED->value;
        logger('KYC verification status check', [
            'reference' => $this->kyc->reference,
            'status' => $latestKyc->status,
            'is_successful' => $isSuccessful
        ]);
        
        return $isSuccessful;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');
        $name = $notifiable?->name ?? $notifiable->username;

        $isSuccessful = $this->isKycSuccessful();

        $mailMessage = (new MailMessage)
            ->subject('BVN Verification Status')
            ->greeting("Hi {$name},");

        $messageBody = $isSuccessful
            ? "Your Bank Verification Number on {$appName} has been approved successfully."
            : "Your Bank Verification Number on {$appName} verification has failed.";

        return $mailMessage->line($messageBody);
    }

    /**
     * Get the Firebase notification representation.
     */
    public function toFirebase(object $notifiable)
    {
        $appName = env('APP_NAME');

        $isSuccessful = $this->isKycSuccessful();

        $title = $isSuccessful ? "BVN Verification Complete" : "BVN Verification Failed";
        $body = $isSuccessful
            ? "Your Bank Verification Number on {$appName} has been approved successfully."
            : "Your Bank Verification Number on {$appName} could not be approved. Please check your details and try again.";

        return (new FirebaseMessage)
            ->withTitle($title)
            ->withBody($body)
            ->withToken($notifiable->device_token)
            ->asNotification();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $appName = env('APP_NAME');

        $isSuccessful = $this->isKycSuccessful();

        $response = [
            'title' => $isSuccessful ? "BVN Verification Complete" : "BVN Verification Failed",
            'message' => $isSuccessful
                ? "Your Bank Verification Number on {$appName} has been approved successfully."
                : "Your Bank Verification Number on {$appName} could not be approved. Please check your details and try again."
        ];

        logger("BVN Verification message feedback", $response);

        return $response;
    }
}
