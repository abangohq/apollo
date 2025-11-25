<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\Tranx;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\MonnifyRequest;
use App\Jobs\User\ResolveTransferEvent;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    /**
     * handle monnify webhook request
     */
    public function monnify(MonnifyRequest $request)
    {
        ResolveTransferEvent::dispatch($request->all(), Tranx::WD_MONNIFY->value);
        return $this->success();
    }

    /**
     * handle redbiller transfer webhook request
     */
    public function redbiller(Request $request)
    {
        logger()->info($request->all());
        ResolveTransferEvent::dispatch($request->all(), Tranx::WD_REDBILLER->value);
        return $this->success();
    }
}
