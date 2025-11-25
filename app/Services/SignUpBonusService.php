<?php

namespace App\Services;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\Reconciliation;
use App\Models\Referral;
use App\Models\ReferralClaim;
use App\Models\SignUpBonus;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\User\SignUpBonusNotification;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignUpBonusService
{
    use WalletEntity;

    /**
     * Create a sign-up bonus for a new user
     */
    public function createSignUpBonus(User $user, float $bonusAmount): SignUpBonus
    {
        $requiredTradeVolume = $requiredTradeVolume ?? (float) env('SIGNUP_BONUS_TRADE_VOLUME', 0);

        return SignUpBonus::create([
            'user_id' => $user->id,
            'bonus_amount' => $bonusAmount,
            'required_trade_volume' => $requiredTradeVolume,
            'current_trade_volume' => 0.00,
            'status' => SignUpBonus::STATUS_PENDING
        ]);
    }

    /**
     * Update user's trade volume and check for bonus unlock
     */
    public function updateTradeVolume(User $user): void
    {
        $signUpBonus = $user->signUpBonus;

        if (!$signUpBonus || $signUpBonus->status !== SignUpBonus::STATUS_PENDING) {
            return;
        }

        $previousTradeVolume = $signUpBonus->current_trade_volume;
        $currentTradeVolume = $user->getTotalCryptoTradeAttribute();
        $signUpBonus->updateTradeVolume($currentTradeVolume);

        // Check for referral rewards when user reaches $200 milestone
        if ($previousTradeVolume < 200 && $currentTradeVolume >= 200) {
            $this->processReferralReward($user);
        }

        // If bonus was unlocked, send notification
        if ($signUpBonus->fresh()->status === SignUpBonus::STATUS_UNLOCKED) {
            $this->sendUnlockNotification($user, $signUpBonus);
        }
    }

    /**
     * Claim the unlocked sign-up bonus
     */
    public function claimBonus(User $user): array
    {
        $signUpBonus = $user->signUpBonus;

        if (!$signUpBonus || !$signUpBonus->canBeClaimed()) {
            throw new \Exception('Sign-up bonus is not available for claiming');
        }

        try {
            DB::beginTransaction();

            $reconciliation = $this->processBonusPayment($user, $signUpBonus);

            $signUpBonus->claim();

            DB::commit();

            $this->sendClaimNotification($user, $signUpBonus);

            Log::info("Sign-up bonus claimed successfully for user {$user->id}", [
                'user_id' => $user->id,
                'bonus_amount' => $signUpBonus->bonus_amount,
                'reference' => $reconciliation->reference
            ]);

            return [
                'success' => true,
                'message' => 'Sign-up bonus claimed successfully',
                'amount' => $signUpBonus->bonus_amount,
                'reference' => $reconciliation->reference
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to claim sign-up bonus for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get sign-up bonus status for a user
     */
    public function getBonusStatus(User $user): ?array
    {
        $signUpBonus = $user->signUpBonus;

        if (!$signUpBonus) {
            return null;
        }

        return [
            'status' => $signUpBonus->status,
            'bonus_amount' => $signUpBonus->bonus_amount,
            'required_trade_volume' => $signUpBonus->required_trade_volume,
            'current_trade_volume' => $signUpBonus->current_trade_volume,
            'progress_percentage' => $signUpBonus->getProgressPercentage(),
            'remaining_trade_volume' => $signUpBonus->getRemainingTradeVolume(),
            'can_claim' => $signUpBonus->canBeClaimed(),
            'unlocked_at' => $signUpBonus->unlocked_at,
            'claimed_at' => $signUpBonus->claimed_at
        ];
    }

    /**
     * Send unlock notification
     */
    private function sendUnlockNotification(User $user, SignUpBonus $signUpBonus): void
    {
        try {
            $user->notify(new SignUpBonusNotification($signUpBonus, 'unlocked'));
        } catch (\Exception $e) {
            Log::error("Failed to send sign-up bonus unlock notification to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send claim notification
     */
    private function sendClaimNotification(User $user, SignUpBonus $signUpBonus): void
    {
        try {
            $user->notify(new SignUpBonusNotification($signUpBonus, 'claimed'));
        } catch (\Exception $e) {
            Log::error("Failed to send sign-up bonus claim notification to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Process bonus payment and create transaction records
     */
    private function processBonusPayment(User $user, SignUpBonus $signUpBonus): Reconciliation
    {
        return $this->processPayment(
            $user,
            $signUpBonus->bonus_amount,
            'sign-up bonus claim',
            "Sign-up bonus NGN{$signUpBonus->bonus_amount} claimed",
            Prefix::BONUS,
            Tranx::BONUS->value
        );
    }

    /**
     * Process referral payment for manual claims
     */
    public function processReferralPayment(User $user, float $amount, string $reason = 'referral claim'): Reconciliation
    {
        return $this->processPayment(
            $user,
            $amount,
            $reason,
            "Referral reward NGN{$amount} claimed",
            Prefix::RECONCILE,
            Tranx::RECONCILE->value
        );
    }

    /**
     * Generic payment processing method
     */
    private function processPayment(
        User $user,
        float $amount,
        string $reason,
        string $narration,
        ?Prefix $referencePrefix = null,
        string $transactionType = null
    ): Reconciliation {
        // Set defaults if not provided
        $referencePrefix = $referencePrefix ?? Prefix::RECONCILE;
        $transactionType = $transactionType ?? Tranx::RECONCILE->value;

        // Credit user's wallet
        $this->credit($user->id, $amount);

        // Create reconciliation record
        $reconciliation = Reconciliation::create([
            'reference' => Utils::generateReference($referencePrefix),
            'user_id' => $user->id,
            'staff_id' => null,
            'origin_tranx_id' => null,
            'entry' => Tranx::CREDIT,
            'amount' => $amount,
            'status' => Status::SUCCESSFUL,
            'reason' => $reason
        ]);

        // Create wallet transaction
        WalletTransaction::create([
            'user_id' => $user->id,
            'reference' => $reconciliation->reference,
            'transaction_type' => $transactionType,
            'transaction_id' => $reconciliation->id,
            'entry' => $reconciliation->entry,
            'status' => Tranx::TRANX_SUCCESS,
            'narration' => $narration,
            'amount' => $amount,
            'charge' => 0,
            'total_amount' => $amount,
            'wallet_balance' => $user->fresh()->wallet->balance
        ]);

        return $reconciliation;
    }

    /**
     * Process referral reward when user reaches $200 trading milestone
     */
    private function processReferralReward(User $user): void
    {
        // Find the referral record for this user (where they were referred by someone)
        $referral = Referral::where('user_id', $user->id)->first();

        if (!$referral || $referral->reward_amount <= 0) {
            return;
        }

        // Find the referrer (the user who owns the referral code)
        $referrer = User::where('referral_code', $referral->code)->first();

        if (!$referrer) {
            return;
        }

        // Check if this referral reward has already been processed
        // We check if there's already a claim record for this specific referral
        $existingClaim = ReferralClaim::where('referral_id', $referral->id)->first();

        if ($existingClaim) {
            return; // Already processed
        }

        try {
            DB::beginTransaction();

            // Create a claim record to track this reward
            ReferralClaim::create([
                'user_id' => $referrer->id, // The referrer gets the reward
                'referral_id' => $referral->id,
                'amount_claimed' => $referral->reward_amount,
                'claimed_at' => now()
            ]);

            // Credit the referrer's wallet
            $this->credit($referrer->id, $referral->reward_amount);

            // Create reconciliation record
            $reconciliation = Reconciliation::create([
                'reference' => Utils::generateReference(Prefix::RECONCILE),
                'user_id' => $referrer->id,
                'staff_id' => null,
                'origin_tranx_id' => null,
                'entry' => Tranx::CREDIT,
                'amount' => $referral->reward_amount,
                'status' => Status::SUCCESSFUL,
                'reason' => 'automatic referral reward'
            ]);

            // Create wallet transaction
            WalletTransaction::create([
                'user_id' => $referrer->id,
                'reference' => $reconciliation->reference,
                'transaction_type' => Tranx::RECONCILE,
                'transaction_id' => $reconciliation->id,
                'entry' => $reconciliation->entry,
                'status' => Tranx::TRANX_SUCCESS,
                'narration' => "Referral reward NGN{$referral->reward_amount} - {$user->name} reached $200 trading milestone",
                'amount' => $referral->reward_amount,
                'charge' => 0,
                'total_amount' => $referral->reward_amount,
                'wallet_balance' => $referrer->fresh()->wallet->balance
            ]);

            DB::commit();

            // Send notification to referrer (optional)
            // $referrer->notify(new ReferralRewardNotification($referral->reward_amount, $user->name));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process referral reward', [
                'user_id' => $user->id,
                'referrer_id' => $referrer->id,
                'referral_id' => $referral->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
