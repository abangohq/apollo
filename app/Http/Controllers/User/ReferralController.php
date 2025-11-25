<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralClaim;
use App\Models\SignUpBonus;
use App\Services\SignUpBonusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    protected SignUpBonusService $signUpBonusService;

    public function __construct(SignUpBonusService $signUpBonusService)
    {
        $this->signUpBonusService = $signUpBonusService;
    }

    /**
     * Get user referral details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferralDetails(Request $request)
    {
        $user = $request->user();

        $totalReferralEarnings = Referral::where('code', $user->referral_code)->sum('reward_amount');

        $referralAmountRedeemed = ReferralClaim::where('user_id', $user->id)->sum('amount_claimed');

        $referralAmountAvailable = $totalReferralEarnings - $referralAmountRedeemed;

        $referredUserIds = Referral::where('code', $user->referral_code)->pluck('user_id');

        $eligibleReferrals = SignUpBonus::whereIn('user_id', $referredUserIds)
            ->where('current_trade_volume', '>=', 200)
            ->count();

        $referralData = [
            'referral_code' => $user->referral_code,
            'referral_amount_available' => $referralAmountAvailable,
            'referral_amount_redeemed' => $referralAmountRedeemed,
            'total_referral_earnings' => $totalReferralEarnings,
            'eligible_referrals' => $eligibleReferrals,
        ];

        $referralData['total_referrals'] = Referral::where('code', $user->referral_code)->count();

        return $this->success($referralData);
    }

    /**
     * Claim referral amount
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function claimReferralAmount(Request $request)
    {
        $user = $request->user();

        // Get all referrals for this user's code
        $referrals = Referral::where('code', $user->referral_code)->get();

        if ($referrals->isEmpty()) {
            return $this->failure('No referrals found', 400);
        }

        // Calculate total earnings and unclaimed amount
        $totalReferralEarnings = $referrals->sum('reward_amount');

        // Get already claimed amount
        $claimedAmount = ReferralClaim::where('user_id', $user->id)->sum('amount_claimed');

        // Calculate available amount
        $availableAmount = $totalReferralEarnings - $claimedAmount;

        // Check if user has any referral amount available
        if ($availableAmount <= 0) {
            return $this->failure('No referral amount available to claim', 400);
        }

        try {
            DB::beginTransaction();

            // Create claim records for unclaimed referrals
            $unclaimedReferrals = $referrals->filter(function ($referral) use ($user) {
                return !ReferralClaim::where('user_id', $user->id)
                    ->where('referral_id', $referral->id)
                    ->exists();
            });

            foreach ($unclaimedReferrals as $referral) {
                ReferralClaim::create([
                    'user_id' => $user->id,
                    'referral_id' => $referral->id,
                    'amount_claimed' => $referral->reward_amount,
                    'claimed_at' => now()
                ]);
            }

            // Process the payment using SignUpBonusService for proper reconciliation
            $reconciliation = $this->signUpBonusService->processReferralPayment(
                $user,
                $availableAmount,
                'manual referral claim'
            );

            DB::commit();

            // Get updated user data
            $updatedUser = $user->fresh();

            $result = [
                'message' => 'Referral amount claimed successfully',
                'claimed_amount' => $availableAmount,
                'new_balance' => $updatedUser->wallet->balance,
                'reference' => $reconciliation->reference
            ];

            return $this->success($result, $result['message']);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failure('Failed to claim referral amount. Please try again.', 500);
        }
    }
}
