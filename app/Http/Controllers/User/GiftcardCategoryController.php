<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GiftcardCategoryCollection;
use App\Models\GiftcardCategory;
use App\Services\SellGiftcardService;
use Illuminate\Http\JsonResponse;

class GiftcardCategoryController extends Controller
{
    private SellGiftcardService $giftcardService;

    public function __construct()
    {
        $this->giftcardService = new SellGiftcardService();
    }

    public function index(): JsonResponse
    {
        $payload = $this->giftcardService->getAllCategories();

        if ($payload->status === 200) {
            return response()->json([
                'categories' => GiftcardCategoryCollection::collection($payload->categories),
            ], $payload->status);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    public function view(GiftcardCategory $giftcardCategory): JsonResponse
    {
        return response()->json([
            'category' => new GiftcardCategoryCollection($giftcardCategory),
        ]);
    }

    public function giftcards(): JsonResponse
    {
        $payload = $this->giftcardService->giftcards();

        if ($payload->status === 200) {
            return response()->json([
                'categories' => $payload->giftcards,
            ], $payload->status);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }
}
