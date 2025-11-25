<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateRateRequest;
use App\Models\CryptoRate;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rates = CryptoRate::all();
        return $this->success($rates, "conversion rates");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRateRequest $request)
    {
        $rate = CryptoRate::create($request->validated());
        return $this->success($rate, "Rate created successfully.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CryptoRate $cryptoRate)
    {
        $cryptoRate->update($request->all());
        return $this->success($cryptoRate, 'Rate updated Successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, CryptoRate $cryptoRate)
    {
        $cryptoRate->delete();
        return $this->success([], 'Rate deleted successfully');
    }
}
