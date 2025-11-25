<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TradeCollection;
use App\Models\Trade;
use App\Services\TradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TradeController extends Controller
{
    private TradeService $tradeService;

    public function __construct()
    {
        $this->tradeService = new TradeService();
    }

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $request->validate([
            'date' => 'nullable|in:today,last_week,last_30_days',
            'ranges' => 'nullable|string',
            'status' => 'nullable|in:pending,rejected,approved',
            'per_page' => 'sometimes|integer|max:200',
        ]);

        $payload = $this->tradeService->getAll($request);

        if ($payload->status === 200) {
            return TradeCollection::collection($payload->trades->paginate($request->per_page ?? 10));
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:500|max:500000',
            'e_code' => 'required_without:images',
            'payout_method' => 'required|in:NGN,USDT',
            'giftcard_id' => 'required|exists:giftcards,id',
            'images' => 'required_without:e_code',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:20000',
        ]);

        $payload = $this->tradeService->create($request);

        if ($payload->status === 201) {
            return response()->json([
                'trade' => new TradeCollection($payload->trade),
            ], $payload->status);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    public function view(Trade $trade): JsonResponse
    {
        $payload = $this->tradeService->view($trade);

        if ($payload->status === 200) {
            return response()->json([
                'trade' => new TradeCollection($trade),
            ]);
        }

        return response()->json([
            'message' => $payload->message,
        ], $payload->status);
    }
}
