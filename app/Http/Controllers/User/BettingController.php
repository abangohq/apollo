<?php

namespace App\Http\Controllers\User;

use App\Actions\Bills\BettingTopupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bills\FundBettingRequest;
use App\Http\Requests\Bills\VerifyBettingAccountRequest;
use App\Models\BettingProduct;
use Illuminate\Http\Request;

class BettingController extends Controller
{
    /**
     * Verify customer betting account
     */
    public function verify(VerifyBettingAccountRequest $request)
    {
        $response = $request->checkAccount();
        return $this->success($response);
    }

    /**
     * Get betting platforms provider
     */
    public function providers(Request $request)
    {
        $products = BettingProduct::query()->active()->get();
        return $this->success($products);
    }

    /**
     * Fund the customer betting account
     */
    public function fund(BettingTopupAction $action)
    {
        $transanction = $action->handle();
        return $this->success($transanction->load('transactable'));
    }
}
