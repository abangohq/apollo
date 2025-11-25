<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\CryptoSwapAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\SwapRateRequest;
use App\Models\SwapTransaction;
use App\Services\Crypto\ChangellyService;
use Illuminate\Http\Request;

class CryptoSwapController extends Controller
{
    public function __construct(public ChangellyService $changellyService)
    {
        //
    }

    /**
     * Get crypto swap rates to show user
     */
    public function swapRate(SwapRateRequest $request)
    {
        $rates = $this->changellyService->swapRates($request->validated());
        return $this->success($rates);
    }

    /**
     * Get the available currencies for swap exchange
     */
    public function currencies(Request $request)
    {
        $currencies = $this->changellyService->currencies();
        return $this->success($currencies['result']);
    }

    /**
     * Get the currency pairs that can be swapped
     */
    public function swapPairs(Request $request)
    {
        $pairs = $this->changellyService->swapPairs($request->from);
        return $this->success($pairs['result']);
    }

    /**
     * Create a new swap for rate gotten above
     */
    public function createSwap(CryptoSwapAction $swapAction)
    {
        $swap = $swapAction->handle();
        return $this->success($swap);
    }

    /**
     * Get swap transaction information
     */
    public function swapDetails(Request $request)
    {
        $swap = SwapTransaction::where('id', $request->swapId)
            ->where('user_id', $request->user()->id)->firstOrFail();

        return $this->success($swap);
    }
}
