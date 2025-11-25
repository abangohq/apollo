<?php

namespace App\Http\Controllers;

use App\Services\BybitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BybitController extends Controller
{
    protected $bybitService;

    public function __construct(BybitService $bybitService)
    {
        $this->bybitService = $bybitService;
    }

    /**
     * Create a spot trade on Bybit
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSpotTrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string',
            'side' => 'required|string|in:Buy,Sell',
            'type' => 'required|string',
            'quantity' => 'required|numeric|gt:0',
            'price' => 'required_if:type,LIMIT|numeric|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->bybitService->createSpotTrade(
                $request->input('symbol'),
                $request->input('side'),
                $request->input('type'),
                $request->input('quantity'),
                $request->input('price')
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create spot trade',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}