<?php

namespace App\Repositories;

use App\Enums\VerificationStatus;
use App\Models\Kyc;
use App\Support\Utils;

class KycRepository
{
   /**
    * Get kycs
    */
   public static function kycs()
   {
      $request = request();
      $status = Utils::withdrawalStatus($request->status);

      $kycs = Kyc::query()->when($status)->where('status', $status)
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "%{$request->search}%")
               ->orWhere('verification_type', 'LIKE', "%{$request->search}%")->orWhere('status', 'LIKE', "%{$request->search}%");
         })
         ->with('user')
         ->orderBy('created_at', 'desc')
         ->paginate();

      $overview['overview']['successful'] = Kyc::where('status', VerificationStatus::SUCCESSFUL)->count();
      $overview['overview']['pending'] = Kyc::where('status', VerificationStatus::PENDING)->count();
      $overview['overview']['rejected'] = Kyc::where('status', VerificationStatus::REJECTED)->count();
      $overview['overview']['abandoned'] = Kyc::where('status', VerificationStatus::ABANDONED)->count();

      $withdrawals = array_merge($kycs->toArray(), $overview);

      return $withdrawals;
   }

   public static function updateKyc(string $id, array $data)
   {
       $kyc = Kyc::find($id);

       if (!$kyc) return null;

       $kyc->update($data);

       $user = $kyc->user;

       if (!$user) return null;

       $deservedTier = $kyc->getDeservedKycTier();

       $user->update(['tier_id' => $deservedTier]);

       return $kyc;
   }

}
