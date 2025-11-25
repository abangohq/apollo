<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Giftcard;
use Illuminate\Http\Request;

class GiftcardController extends Controller
{
    public function index()
    {
        $giftcards = Giftcard::with('giftcardCategory')->orderBy('sort_order')->paginate(50);
        return $this->success($giftcards, 'Giftcards');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'giftcard_category_id' => 'required|exists:giftcard_categories,id',
            'name' => 'required|string|max:255',
            'image' => 'sometimes',
            'wait_time' => 'sometimes|integer|min:0',
            'minimum_amount' => 'required|integer|min:0',
            'maximum_amount' => 'required|integer|min:0',
            'currency' => 'required|in:NGN,USD,GBP,EUR',
            'high_rate' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'terms' => 'sometimes|string|nullable',
            'rate' => 'required|integer|min:0',
            'sort_order' => 'sometimes|integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->storeOnCloudinaryAs('giftcards/items')->getSecurePath();
        }

        $giftcard = Giftcard::create($validated);
        $giftcard->load('giftcardCategory');
        return $this->success($giftcard, 'Giftcard created');
    }

    public function show(Giftcard $giftcard)
    {
        $giftcard->load('giftcardCategory');
        return $this->success($giftcard, 'Giftcard');
    }

    public function update(Request $request, Giftcard $giftcard)
    {
        $validated = $request->validate([
            'giftcard_category_id' => 'sometimes|exists:giftcard_categories,id',
            'name' => 'sometimes|string|max:255',
            'image' => 'sometimes',
            'wait_time' => 'sometimes|integer|min:0',
            'minimum_amount' => 'sometimes|integer|min:0',
            'maximum_amount' => 'sometimes|integer|min:0',
            'currency' => 'sometimes|in:NGN,USD,GBP,EUR',
            'high_rate' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'terms' => 'sometimes|string|nullable',
            'rate' => 'sometimes|integer|min:0',
            'sort_order' => 'sometimes|integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->storeOnCloudinaryAs('giftcards/items')->getSecurePath();
        }

        $giftcard->update($validated);
        $giftcard->load('giftcardCategory');
        return $this->success($giftcard, 'Giftcard updated');
    }

    public function destroy(Giftcard $giftcard)
    {
        $giftcard->delete();
        return $this->success([], 'Giftcard deleted');
    }
}