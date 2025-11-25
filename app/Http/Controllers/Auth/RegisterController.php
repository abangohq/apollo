<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterAction;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    /**
     * Register a user and federate into our app
     */
    public function __invoke(RegisterAction $registerAction)
    {
        return $registerAction->handle();
    }
}
