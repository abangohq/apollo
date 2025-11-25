<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\SignUpBonusService;
use App\Traits\RespondsWithHttpStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignUpBonusController extends Controller
{
    use RespondsWithHttpStatus;

    protected SignUpBonusService $signUpBonusService;

    public function __construct(SignUpBonusService $signUpBonusService)
    {
        $this->signUpBonusService = $signUpBonusService;
    }

    /**
     * Get the authenticated user's sign-up bonus status
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        $bonusStatus = $this->signUpBonusService->getBonusStatus($user);

        if (!$bonusStatus) {
            return $this->success([
                'has_bonus' => false,
                'message' => 'No sign-up bonus available'
            ]);
        }

        return $this->success([
            'has_bonus' => true,
            'bonus' => $bonusStatus
        ]);
    }

    /**
     * Claim the unlocked sign-up bonus
     */
    public function claim(Request $request)
    {
        try {
            $user = Auth::user();
            $result = $this->signUpBonusService->claimBonus($user);

            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage());
        }
    }
}
