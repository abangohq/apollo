<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BiometricCheckRequest;
use App\Http\Requests\Auth\BiometricRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PinLoginRequest;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Federate user into our application
     */
    public function login(LoginRequest $request)
    {
        $user = $request->checkedUser();
        return $this->success($request->federate($user), 'login successful');
    }

    /**
     * log the federated user out from the app
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success(null, 'user, logged out successfully.');
    }

    /**
     * Authenticate user with pin
     */
    public function pinLogin(PinLoginRequest $request)
    {
        return $this->success($request->user());
    }

    /**
     * Set biomentric
     */
    public function setBiometric(BiometricRequest $request)
    {
        $request->user()->update($request->biometric());
        return $this->success($request->user()->refresh(), 'biometric set successfully.');
    }

    /**
     * Undo biometric
     */
    public function removeBiometric(Request $request)
    {
        $request->user()->update(['has_biometric' => false, 'face_id' => null, 'touch_id' => null]);
        return $this->success($request->user()->refresh(), 'biometric removed successfully.');
    }

    /**
     * Check if a bio metric has is valid
     */
    public function checkBiometric(BiometricCheckRequest $request)
    {
        return $this->success($request->user(), 'biometric checked successfully.');
    }
}
