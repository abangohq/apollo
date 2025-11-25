<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pin\CheckPinRequest;
use App\Http\Requests\Pin\CreatePinRequest;
use App\Http\Requests\Pin\UnsetPinRequest;
use App\Http\Requests\Pin\UpdatePinRequest;
use App\Notifications\User\PinUpdateNotice;
use Illuminate\Http\Request;

class PinController extends Controller
{
    /**
     * Create a ne transaction pin
     */
    public function createPin(CreatePinRequest $request)
    {
        $request->createPin();
        return $this->success(null, "Your pin has been saved successfully");
    }

    /**
     * Check if pin is valid for actions
     */
    public function verifyPin(CheckPinRequest $request)
    {
        return $this->success(null, "Pin checked successfully.");
    }

    /**
     * Update the users pin
     */
    public function updatePin(UpdatePinRequest $request)
    {
        $request->user()->update($request->pinAttributes());
        $request->user()->notify(new PinUpdateNotice);
        return $this->success(null, "Your pin has been updated successfully");
    }

    /**
     * Unset the user created pin
     */
    public function destroyPin(UnsetPinRequest $request)
    {
        $request->user()->update($request->pinAttributes);
        return $this->success(null, "PIN deleted successfully");
    }
}
