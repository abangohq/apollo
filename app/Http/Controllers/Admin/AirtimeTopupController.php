<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAirtimeProductRequest;
use App\Models\AirtimeProduct;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class AirtimeTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get all airtimes tops for users 
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersAirtimeTopups();
        return $this->success($topups, 'Airtime Purchases');
    }

    /**
     * Get the airtime products
     */
    public function products(Request $request)
    {
        $products = AirtimeProduct::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Update airtime prodduct information
     */
    public function update(UpdateAirtimeProductRequest $request, AirtimeProduct $airtimeProduct)
    {        
        $airtimeProduct->update($request->productAttributes());
        return $this->success($airtimeProduct, 'Product updated successfully!');
    }
}
