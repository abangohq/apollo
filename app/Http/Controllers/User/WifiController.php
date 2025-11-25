<?php

namespace App\Http\Controllers\User;

use App\Actions\Bills\WifiTopupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bills\VerifyWifiRequest;
use App\Models\WifiProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class WifiController extends Controller
{
    public function __construct(public BillRepository $billRepo)
    {
    }
    /**
     * Get the wifi plans for customers to purchase
     */
    public function plans(Request $request)
    {
        $plans = $this->billRepo->wifiPlans($request->product);
        return $this->success(['categories' => $plans]);
    }

    /**
     * Verify the modem device information
     */
    public function verifyDevice(VerifyWifiRequest $request)
    {
        $device = $request->checkDevice();
        return $this->success($device);
    }

    /**
     * Get the wifi providders 
     */
    public function providers(Request $request)
    {
        $providers = WifiProvider::all();
        return $this->success($providers);
    }

    /**
     * Purchase the wifi plan
     */
    public function purchase(WifiTopupAction $action)
    {
        $transactions = $action->handle();
        return $this->success($transactions->load('transactable'));
    }
}
