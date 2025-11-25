<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Finance\BalanceSweepAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAssetRequest;
use App\Http\Requests\Admin\UpdateAssetRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoTransaction;
use App\Models\CryptoWithdrawal;
use App\Repositories\CryptoRepository;
use App\Services\Crypto\VaultodyService;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    public function __construct(protected CryptoRepository $cryptoRepo, public VaultodyService $vaultodyService)
    {
        //
    }

    /**
     * Retrieve crypto assets with conversion rate
     */
    public function index(Request $request)
    {
        $assets = CryptoAsset::query()->oldest('name')->with('conversionRates')
            ->when($request->has('status'))->where('status', $request->status)
            ->get();

        return $this->success($assets, 'Crypto Assets');
    }

    /**
     * Retrieve the crypto asset details
     */
    public function show(Request $request, $id)
    {
        $purchase = CryptoAsset::with('conversionRates')->find($id);
        return $this->success($purchase, 'Crypto Asset');
    }

    /**
     * Create new Crypto Asset details
     */
    public function create(CreateAssetRequest $request, CryptoAsset $cryptoAsset)
    {
        $cryptoAsset = CryptoAsset::create($request->assetAttributes());
        return $this->success($cryptoAsset, 'Asset created successfully.');
    }

    /**
     * Update Crypto Asset details
     */
    public function update(UpdateAssetRequest $request, CryptoAsset $cryptoAsset)
    {
        $cryptoAsset->update($request->assetAttributes());
        return $this->success($cryptoAsset, 'Asset updated successfully.');
    }

    /**
     * Retrieve crypto transactions by users
     */
    public function transactions(Request $request)
    {
        $deposits = $this->cryptoRepo->usersTransactions();
        return $this->success($deposits, 'Crypto Deposits');
    }

    /**
     * Retrieve crypto swap transactions by users
     */
    public function swaps(Request $request)
    {
        $deposits = $this->cryptoRepo->swapTransactions();
        return $this->success($deposits, 'Crypto swaps');
    }

    /**
     * Show crypto transaction details
     */
    public function transaction(Request $request, CryptoTransaction $cryptoTransaction)
    {
        $purchase = $cryptoTransaction->load('user');
        return $this->success($purchase, 'Deposit details');
    }

    /**
     * Retrieve wallet asset balance
     */
    public function walletBalance(Request $request, VaultodyService $service)
    {
        $balance = $service->getWalletAssetDetails();
        return $this->success($balance);
    }

    /**
     * Withdraw balance
     */
    public function withdraw(BalanceSweepAction $action)
    {
        $response = $action->handle();
        return $this->success($response);
    }

    /**
     * Fee estimate before withdrawal transction
     */
    public function estimate(Request $request) 
    {
       $response = $this->vaultodyService->estimate();
       return $this->success($response);
    }

    public function sweepHistory(Request $request)
    {
        $sweeps = CryptoWithdrawal::orderBy('created_at', 'desc')->paginate(25);

        return $this->success($sweeps);
    }
}
