<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBetProductRequest;
use App\Models\BettingProduct;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class BettingTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get users airtime purchases
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersBetTopups();
        return $this->success($topups, 'Betting Top Ups');
    }

    /**
     * Get the bill products for betting
     */
    public function products(Request $request)
    {
        $products = BettingProduct::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Update betting products
     */
    public function update(UpdateBetProductRequest $request, BettingProduct $bettingProduct)
    {
        $bettingProduct->update($request->productAttributes());
        return $this->success($bettingProduct, 'Product updated successfully.');
    }
}
