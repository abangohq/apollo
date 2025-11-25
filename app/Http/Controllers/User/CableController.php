<?php

namespace App\Http\Controllers\User;

use App\Actions\Bills\CableTopupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bills\VerifySmartcardRequest;
use App\Models\CableProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class CableController extends Controller
{
    public function __construct(public BillRepository $billRepo)
    {
        //
    }

    /**
     * Get the list of cabletv product plans
     */
    public function plans(Request $request)
    {
        $plans = $this->billRepo->cablePlans($request->provider);
        return $this->success($plans);
    }

    /**
     * Retrieve the cabletv providers
     */
    public function providers(Request $request)
    {
        $providers = CableProvider::all();
        return $this->success($providers);
    }

    /**
     * Purchase a cable tv plan
     */
    public function purchase(CableTopupAction $action)
    {
        $response = $action->handle();
        return $this->success($response->load('transactable'));
    }

    /**
     * Verify smart card for validity
     */
    public function verifySmartCard(VerifySmartcardRequest $request)
    {
        $card = $request->checkCard();
        return $this->success($card);
    }
}
