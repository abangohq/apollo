<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\CreateReferralCodeRequest;
use App\Http\Requests\Admin\Finance\UpdateReferralCodeRequest;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Repositories\GeneralRepository;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    /**
     * Get all referal codes
     */
    public function codes(Request $request)
    {
        $codes = GeneralRepository::referralCodes();
        return $this->success($codes);
    }

    /**
     * Get all referrals  done
     */
    public function referrals(Request $request)
    {
        $referrals = GeneralRepository::referrals();
        return $this->success($referrals);
    }

    /**
     * Create a new referral code
     */
    public function createCode(CreateReferralCodeRequest $request)
    {
        $code = ReferralCode::create($request->refAttributes());
        
        return $this->success($code);
    }

    /**
     * update a referral code
     */
    public function updateCode(UpdateReferralCodeRequest $request, ReferralCode $referralCode)
    {
        $referralCode->update($request->validated());
        return $this->success($referralCode);
    }

    /**
     * Delete a referral code
     */
    public function deleteCode(Request $request, ReferralCode $referralCode)
    {
        $referralCode->delete();
        return $this->success();
    }

    public function signUpBonuses(Request $request)
    {
        $bonuses = GeneralRepository::signUpBonuses();
        return $this->success($bonuses);
    }
}

