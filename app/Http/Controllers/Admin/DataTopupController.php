<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDataProductRequest;
use App\Models\IspProvider;
use App\Repositories\BillRepository;
use Illuminate\Http\Request;

class DataTopupController extends Controller
{
    public function __construct(public BillRepository $billRepository)
    {
        //
    }

    /**
     * Get users data topup purchases
     */
    public function topups(Request $request)
    {
        $topups = $this->billRepository->usersDataTopups();
        return $this->success($topups, 'Data Purchases');
    }

    /**
     * Get the data isp product provider
     */
    public function products(Request $request)
    {
        $products = IspProvider::all();
        return $this->success($products, 'Supported Products');
    }

    /**
     * Update the isp provider product
     */
    public function update(UpdateDataProductRequest $request, IspProvider $ispProvider)
    {
        $ispProvider->update($request->productAttributes());
        return $this->success($ispProvider, 'Product updated successfully!');
    }
}
