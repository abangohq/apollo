<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GiftcardCollection;
use App\Models\Giftcard;
use App\Models\GiftcardCategory;
use App\Services\SellGiftcardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftcardController extends Controller
{
    private SellGiftcardService $giftcardService;

    public function __construct()
    {
        $this->giftcardService = new SellGiftcardService();
    }

    public function index(GiftcardCategory $giftcardCategory, Request $request): JsonResponse
    {
        $request->validate([
            'high_rate' => 'nullable|boolean',
        ]);

        $payload = $this->giftcardService->getAllGiftCards($giftcardCategory, $request);

        if ($payload->status === 200) {
            return response()->json([
                'giftcards' => GiftcardCollection::collection($payload->giftcards),
            ], $payload->status);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    public function highRate(Request $request): JsonResponse
    {
        $payload = $this->giftcardService->getAllHighRateGiftCards($request);

        if ($payload->status === 200) {
            return response()->json([
                'giftcards' => GiftcardCollection::collection($payload->giftcards),
            ], $payload->status);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    public function view(GiftcardCategory $giftcardCategory, Giftcard $giftcard): JsonResponse
    {
        return response()->json([
            'giftcard' => new GiftcardCollection($giftcard),
        ]);
    }
}
