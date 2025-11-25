<?php

namespace App\Repositories;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\SignUpBonus;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

class GeneralRepository
{
   /**
    * Retrieve application system settings
    */
   public static function paySettings()
   {
      return [
         'payment_gateway' => SystemSetting::where('key', 'payment_gateway')->value('value'),
         'withdrawal_mode' => SystemSetting::where('key', 'withdrawal_mode')->value('value'),
         'withdrawal_limit' => (float) SystemSetting::where('key', 'max_automatic_withdrawal_amount')->value('value'),
         'sweep_address' => SystemSetting::where('key', 'sweep_address')->value('value')
      ];
   }

   /**
    * Get the list of referral codes 
    */
   public static function referralCodes()
   {
      $request = request();

      return ReferralCode::select('referral_codes.*', DB::raw('COUNT(referrals.id) as total_uses'))
        ->leftJoin('referrals', 'referrals.code', '=', 'referral_codes.code')
        ->when($request->boolean('active'), function ($query) {
            $query->where('referral_codes.active', true);
        })
        ->when($request->filled('search'), function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('referral_codes.code', 'LIKE', "%{$request->search}%")
                    ->orWhere('referral_codes.amount', 'LIKE', "%{$request->search}%");
            });
        })
        ->groupBy('referral_codes.id')
        ->paginate();
   }

   /**
    * Get referrals
    */
   public static function referrals()
   {
      $request = request();

      return Referral::select('referrals.*')
         ->with('referredBy')
         ->when($request->filled('search'), function ($query) use ($request) {
         $query->where(function ($q) use ($request) {
            $q->whereHas('user', function ($userQuery) use ($request) {
            $userQuery->where('username', 'LIKE', "%{$request->search}%")
                   ->orWhere('email', 'LIKE', "%{$request->search}%");
            })->orWhere('referrals.code', 'LIKE', "%{$request->search}%");
         });
         })
         ->orderBy('referrals.created_at', 'desc')
         ->paginate();
   }

   public static function signUpBonuses()
   {
      $request = request();

      return SignUpBonus::select('sign_up_bonuses.*')
         ->whereNotNull('sign_up_bonuses.unlocked_at')
         ->with('user')
         ->when($request->filled('search'), function ($query) use ($request) {
            $query->whereHas('user', function ($userQuery) use ($request) {
               $userQuery->where('username', 'LIKE', "%{$request->search}%")
                         ->orWhere('email', 'LIKE', "%{$request->search}%");
            });
         })
         ->orderBy('sign_up_bonuses.created_at', 'desc')
         ->paginate();
   }
}
