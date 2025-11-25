<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\CryptoWalletAction;
use App\Http\Controllers\Controller;
use App\Models\CryptoAsset;
use App\Repositories\CryptoRepository;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    public function __construct(public CryptoRepository $cryptoRepo)
    {
        //
    }

    /**
     * Get the wallet info for a crypto asset.
     */
    public function wallet(Request $request, CryptoAsset $cryptoAsset)
    {
        $wallet = $this->cryptoRepo->wallet($cryptoAsset);
        return $this->success($wallet);
    }

    /**
     * Create asset wallet address for you user
     */
    public function createWallet(CryptoWalletAction $action)
    {
        $wallet = $action->handle();
        return $this->success($wallet);
    }

    /**
     * Retrieve all crypto assets information
     */
    public function assets(Request $request)
    {
        $assets = $this->cryptoRepo->assets($request->boolean('graph'));
        return $this->success($assets, 'Crypto assets');
    }

    /**
     * Get single crypto asset information
     */
    public function asset(Request $request, $symbol)
    {
        $crypto = CryptoAsset::active()->whereSymbol($symbol)->firstOrFail();
        $crypto->currency  = "USD";

        return $this->success($crypto, 'Crypto asset');
    }

    /**
     * Retrive the crypto conversion rates to use
     */
    public function rates(Request $request)
    {
        $rates = $this->cryptoRepo->rates();
        return $this->success($rates);
    }
}
