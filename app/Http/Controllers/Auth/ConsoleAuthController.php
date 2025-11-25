<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\ProfileUpdateRequest;
use App\Http\Requests\Auth\ConsoleLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ConsoleAuthController extends Controller
{
    /**
     * Federate user into our application
     */
    public function login(ConsoleLoginRequest $request)
    {
        $user = $request->checkedUser();
        return $this->success($request->federate($user), 'login successful');
    }

    /**
     * log the federated user out from the app
     */
    public function logout(Request $request)
    {
        $request->user()->update(['last_logout' => now()]);
        $request->user()->tokens()->delete();

        return $this->success(null, 'User logged out successfully.');
    }

    /**
     * Update the authenticated user password
     */
    public function changePassword(ChangePasswordRequest $request) 
    {
        $request->user()->update($request->passwordAttr());
        return $this->success($request->user(), 'password updated successfully');
    }

    /**
     *  User Profile information update
     */
    public function update(ProfileUpdateRequest $request, User $user)
    {
        $user->update($request->validated());
        return $this->success($user, 'Profile updated successfully .');
    }
}
