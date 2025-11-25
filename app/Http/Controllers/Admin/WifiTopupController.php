<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWifiProductRequest;
use App\Models\WifiProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class WifiTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get the users wifi plans purchases
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersWifiTopups();
        return $this->success($topups);
    }

    /**
     * Get wifi providers products
     */
    public function products(Request $request)
    {
        $products = WifiProvider::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Wifi plans provider products
     */
    public function update(UpdateWifiProductRequest $request, WifiProvider $wifiProvider)
    {
        $wifiProvider->update($request->productAttributes());
        return $this->success($wifiProvider, 'Product updated successfully!');
    }
}
