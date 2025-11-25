<?php

namespace App\Http\Controllers\User;

use App\Actions\Bills\MeterTopupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bills\VerifyMeterRequest;
use App\Models\MeterProduct;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    /**
     * Purchase meter amount
     */
    public function purchase(MeterTopupAction $action)
    {
        $transaction = $action->handle();
        return $this->success($transaction->load('transactable'));
    }

    /**
     * Get meter power region providers
     */
    public function providers(Request $request)
    {
        $providers = MeterProduct::all();
        return $this->success($providers);
    }

    /**
     * verify meter number if valid
     */
    public function verifyMeter(VerifyMeterRequest $request)
    {
        $response = $request->checkMeterNunber();
        return $this->success($response);
    }
}
