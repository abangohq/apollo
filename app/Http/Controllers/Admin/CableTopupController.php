<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCableProductRequest;
use App\Models\CableProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class CableTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get the airtime topup products
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersCableTopups();
        return $this->success($topups, 'Cable TV Purchases');
    }

    /**
     * Get the airtime product for this bill
     */
    public function products(Request $request)
    {
        $products = CableProvider::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Update airtime product information
     */
    public function update(UpdateCableProductRequest $request, CableProvider $cableProvider)
    {
        $cableProvider->update($request->productAttributes());
        return $this->success($cableProvider, 'Product updated successfully!');
    }
}
