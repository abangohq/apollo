<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CheckTokenRequest;
use App\Http\Requests\Auth\CreatePasswordRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\PinChangeRequest;
use App\Http\Requests\Auth\PinResetRequest;
use App\Models\User;
use App\Models\VerifyToken;
use Illuminate\Http\Request;

class ResetsController extends Controller
{
    /**
     * Request pin reset OTP pasword
     */
    public function pinRequest(PinResetRequest $request)
    {
        return $this->success(null, 'One time password for otp reset has been sent.');
    }

    /**
     * OTP Token confirmation for resets
     */
    public function check(CheckTokenRequest $request)
    {
        return $this->success(null, 'One time password checked successfully.');
    }

    /**
     * Reset user transaction pin
     */
    public function pinReset(PinChangeRequest $request)
    {
        $request->user()->update($request->pinAttributes());
        VerifyToken::whereEmail($request->user()->email)->delete();
        return $this->success(message: 'Pin reset successfully');
    }

    /**
     * Request password reset OTP pasword
     */
    public function passwordRequest(PasswordResetRequest $request)
    {
        return $this->success(null, 'One time password for otp reset has been sent.');
    }

    /**
     * Request password reset OTP pasword
     */
    public function passwordReset(CreatePasswordRequest $request)
    {
        User::whereEmail($request->email)->update($request->passwordAttr());
        return $this->success(null, 'password updated successfully.');
    }
}
