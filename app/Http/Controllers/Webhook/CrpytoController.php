<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\CoinEventType;
use App\Http\Controllers\Controller;
// use App\Http\Requests\Webhook\CryptoApisRequest;
// use App\Jobs\Crypto\ResolveCryptoApis;
use App\Http\Requests\Webhook\VaultodyRequest;
use App\Http\Requests\Webhook\XprocessingRequest;
use App\Jobs\Crypto\ResolveVaultody;
use App\Jobs\Crypto\ResolveXprocessing;
use Illuminate\Http\Request;

class CrpytoController extends Controller
{
    /**
     * Handle 0xprocessing webhook request
     */
    public function xprocessing(XprocessingRequest $request)
    {
        ResolveXprocessing::dispatch($request->all());
        return $this->success();
    }

    /**
     * Handle crypto apis webhook request
     */
    public function cryptoapis($request)
    {
       // Removed: CryptoApis integration
       return response()->json('gone', 410);
    }

    /**
     * Handle crypto vaultody webhook request
     */
    public function vaultody(VaultodyRequest $request)
    {
        ResolveVaultody::dispatch($request->all());

        return $this->success();
    }

    /**
     * Handle changelly webhook request
     */
    public function changelly(Request $request)
    {
        // webhook
    }
}
