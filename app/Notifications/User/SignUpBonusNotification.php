<?php

namespace App\Notifications\User;

use App\Models\SignUpBonus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;

class SignUpBonusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected SignUpBonus $signUpBonus;
    protected string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(SignUpBonus $signUpBonus, string $type = 'unlocked')
    {
        $this->queue = 'notifications';
        $this->signUpBonus = $signUpBonus;
        $this->type = $type; // 'unlocked' or 'claimed'
        $this->afterCommit();
        $this->delay(now()->addMinutes(1));
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->type === 'unlocked') {
            return (new MailMessage)
                ->subject('🎉 Your Sign-Up Bonus is Ready!')
                ->greeting('Congratulations!')
                ->line("Great news! You've successfully traded the required $200 and your sign-up bonus of NGN{$this->signUpBonus->bonus_amount} is now unlocked.")
                ->line('You can now claim your bonus from your wallet.')
                ->action('Claim Bonus', url('/wallet'))
                ->line('Thank you for trading with us!');
        }

        return (new MailMessage)
            ->subject('✅ Sign-Up Bonus Claimed Successfully')
            ->greeting('Bonus Claimed!')
            ->line("Your sign-up bonus of NGN{$this->signUpBonus->bonus_amount} has been successfully added to your wallet.")
            ->line('You can now use this balance for trading or withdraw it to your bank account.')
            ->line('Happy trading!');
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase($notifiable): FirebaseMessage
    {
        if ($this->type === 'unlocked') {
            return (new FirebaseMessage)
                ->withTitle('🎉 Sign-Up Bonus Unlocked!')
                ->withBody("Congratulations! Your NGN{$this->signUpBonus->bonus_amount} sign-up bonus is ready to claim.")
                ->withAdditionalData([
                    'type' => 'sign_up_bonus_unlocked',
                    'bonus_amount' => $this->signUpBonus->bonus_amount,
                    'action' => 'claim_bonus'
                ]);
        }

        return (new FirebaseMessage)
            ->withTitle('✅ Bonus Claimed Successfully')
            ->withBody("NGN{$this->signUpBonus->bonus_amount} has been added to your wallet.")
            ->withAdditionalData([
                'type' => 'sign_up_bonus_claimed',
                'bonus_amount' => $this->signUpBonus->bonus_amount,
                'action' => 'view_wallet'
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->type === 'unlocked') {
            return [
                'type' => 'sign_up_bonus_unlocked',
                'title' => '🎉 Sign-Up Bonus Unlocked!',
                'message' => "Congratulations! Your NGN{$this->signUpBonus->bonus_amount} sign-up bonus is ready to claim.",
                'bonus_amount' => $this->signUpBonus->bonus_amount,
                'action' => 'claim_bonus',
                'created_at' => now()->toISOString()
            ];
        }

        return [
            'type' => 'sign_up_bonus_claimed',
            'title' => '✅ Bonus Claimed Successfully',
            'message' => "NGN{$this->signUpBonus->bonus_amount} has been added to your wallet.",
            'bonus_amount' => $this->signUpBonus->bonus_amount,
            'action' => 'view_wallet',
            'created_at' => now()->toISOString()
        ];
    }
}
