<?php

namespace App\Http\Controllers\Admin;

use App\Casts\RoleCast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\Request;

class StaffsController extends Controller
{
    /**
     * Get the list of all staffs
     */
    public function staffs(Request $request)
    {
        $staffs = User::query()->whereUserType('staff')->get();
        $staffs->makeVisible('role');
        return $this->success($staffs);
    }

    /**
     * Create new staff record with role
     */
    public function create(CreateStaffRequest $request)
    {
        $user = User::unguarded(fn () => User::create($request->userAttributes()));
        $user->makeVisible('role');
        return $this->success($user);
    }

    /**
     * update staff record
     */
    public function update(UpdateStaffRequest $request, User $user)
    {
        User::unguarded(fn () => $user->update($request->validated()));
        $user->makeVisible('role');
        return $this->success($user);
    }

    /**
     * Delete a staff (soft delete)
     */
    public function delete(Request $request, User $user)
    {
        $user->delete();
        return $this->success([], 'User deleted successfully');
    }

    /**
     * Fetch available staff roles for the dashboard.
     */
    public function roles(Request $request)
    {
        return $this->success(RoleCast::roles());
    }
}
