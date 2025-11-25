<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMeterProductRequest;
use App\Models\MeterProduct;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class MeterTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get the list of users topups
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersMeterTopups();
        return $this->success($topups, 'Disco Purchases');
    }

    /**
     * Get provider products list
     */
    public function products(Request $request)
    {
        $products = MeterProduct::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Update meter product information
     */
    public function update(UpdateMeterProductRequest $request, MeterProduct $meterProduct)
    {
        $meterProduct->update($request->productAttributes());
        return $this->success($meterProduct, 'Product updated successfully!');
    }
}
