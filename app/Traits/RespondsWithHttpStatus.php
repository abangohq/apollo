<?php

namespace App\Traits;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

trait RespondsWithHttpStatus
{
    /**
     * Json success response helper without extra headers
     * and body
     * 
     * @param  mixed  $message
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = [], $message = 'successful')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    /**
     * Json post request response helper
     *
     * @param  mixed  $message
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function failure($message = 'Error occured while processing your request', $status = 409)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Throw validation with json repoonse
     *
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validateResp($errors = [])
    {
        throw ValidationException::withMessages($errors);
    }

    /**
     * Federate the user into our application
     */
    public function federate(User $user): array
    {
        return [
            'token' => $user->createToken('authToken')->plainTextToken,
            'user' =>  new UserResource($user)
        ];
    }
}
