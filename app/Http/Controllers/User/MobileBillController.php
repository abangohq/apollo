<?php

namespace App\Http\Controllers\User;

use App\Actions\Bills\AirtimeTopupAction;
use App\Actions\Bills\DataTopupAction;
use App\Http\Controllers\Controller;
use App\Models\IspProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class MobileBillController extends Controller
{
    public function __construct(public BillRepository $phonebillRepo)
    {
        //
    }

    /**
     * Get both airtime and data isp provider
     */
    public function providers(Request $request)
    {
        $provider = IspProvider::whereStatus('active')->get();
        return $this->success($provider);
    }

    /**
     * Get data plans for a network provider
     */
    public function dataPlans(Request $request)
    {
        $plans = $this->phonebillRepo->dataPlans($request->network);
        return $this->success(['categories' => $plans], 'Plans');
    }

    /**
     * Purchase data plan
     */
    public function purchaseData(DataTopupAction $action)
    {
        $transaction = $action->handle();
        return $this->success($transaction->load('transactable'));
    }

    /**
     * Purchase an airtime amount
     */
    public function purchaseAirtime(AirtimeTopupAction $action)
    {
        $transaction = $action->handle();
        return $this->success($transaction->load('transactable'));
    }
}
