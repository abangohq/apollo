<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\EditProfileRequest;
use App\Http\Requests\Profile\PasswordRequest;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Edit the auth user profile
     */
    public function edit(EditProfileRequest $request)
    {
        $request->user()->update($request->profileAttributes());
        return $this->success(new UserResource($request->user()->refresh()), 'Profile updated successfully.');
    }

    /**
     * Soft deletes a user account
     */
    public function destroy(DeleteAccountRequest $request)
    {
        $request->user()->tokens()->delete();
        $request->user()->delete();
        return $this->success(null, "User account deleted successfully.");
    }

    /**
     * Update password for auth user
     */
    public function updatePassword(PasswordRequest $request)
    {
        $request->user()->update($request->passwordAttr());
        return $this->success(new UserResource($request->user()), 'password updated successfully');
    }

    /**
     * Save the fcm notification device token
     */
    public function saveDeviceToken(Request $request)
    {
        $payload = $request->validate(['device_token' => 'required']);
        $request->user()->update($payload);
        return $this->success($request->user(), 'Successfully saved device token');
    }

    /**
     * Delete the fcm notification device token
     */
    public function deleteDeviceToken(Request $request)
    {
        $request->user()->update(['device_token' => null]);
        return $this->success($request->user(), 'Successfully deleted device token');
    }

    /**
     * Fetch auth user information
     */
    public function userDetails(Request $request)
    {
        $user = $request->user()->load(['kycs', 'banks']);
        return $this->success($user, 'user information');
    }

    /**
     * Fetch user tier information
     */
    public function fetchTiers(Request $request, UserRepository $userRepo)
    {
        $tiers = $userRepo->getAuthUserTier();
        return $this->success($tiers, 'Tiers');
    }
}
