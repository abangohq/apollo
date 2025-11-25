<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\Bills\ResolveAirtimePurchase;
use App\Jobs\Bills\ResolveBettingPurchase;
use App\Jobs\Bills\ResolveCablePurchase;
use App\Jobs\Bills\ResolveDataPurchase;
use App\Jobs\Bills\ResolveMeterPurchase;
use App\Jobs\Bills\ResolveWifiPurchase;
use App\Models\AirtimeTopUp;
use App\Models\BettingTopUp;
use App\Models\CableTopUp;
use App\Models\DataTopUp;
use App\Models\MeterTopUp;
use App\Models\WifiTopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Conditionable;

class RedbillerController extends Controller
{
    use Conditionable;

    /**
     * Handle data purchahse webhook
     */
    public function data(Request $request)
    {
        $tp = DataTopUp::findByRef($request->input('details.reference'))->first();
        $this->when($tp, fn () => ResolveDataPurchase::dispatch($tp));
        
        return $this->success();
    }

    /**
     * Handle airtime purchahse webhook
     */
    public function airtime(Request $request)
    {
        $tp = AirtimeTopUp::findByRef($request->input('details.reference'))->firstOrFail();
        $this->when($tp, fn () => ResolveAirtimePurchase::dispatch($tp));

        return $this->success();
    }

    /**
     * Handle betting purchahse webhook
     */
    public function betting(Request $request)
    {
        $tp = BettingTopUp::findByRef($request->input('details.reference'))->firstOrFail();
        $this->when($tp, fn () => ResolveBettingPurchase::dispatch($tp));

        return $this->success();
    }

    /**
     * Handle meter purchahse webhook
     */
    public function meter(Request $request)
    {
        $tp = MeterTopUp::findByRef($request->input('details.reference'))->firstOrFail();
        $this->when($tp, fn () => ResolveMeterPurchase::dispatch($tp));

        return $this->success();
    }

    /**
     * Handle cable purchahse webhook
     */
    public function cable(Request $request)
    {
        $tp = CableTopUp::findByRef($request->input('details.reference'))->firstOrFail();
        $this->when($tp, fn () => ResolveCablePurchase::dispatch($tp));

        return $this->success();
    }

    /**
     * Handle wifi purchahse webhook
     */
    public function wifi(Request $request)
    {
        $tp = WifiTopUp::findByRef($request->input('details.reference'))->firstOrFail();
        $this->when($tp, fn () => ResolveWifiPurchase::dispatch($tp));

        return $this->success();
    }
}
