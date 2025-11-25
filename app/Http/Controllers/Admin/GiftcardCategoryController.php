<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftcardCategory;
use Illuminate\Http\Request;

class GiftcardCategoryController extends Controller
{
    public function index()
    {
        $categories = GiftcardCategory::query()->withCount('giftcards')->orderBy('sort_order')->paginate(50);
        return $this->success($categories, 'Giftcard categories');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
            'logo_image' => 'sometimes',
            'preview_image' => 'sometimes',
        ]);

        // Optional file uploads for logo/preview
        if ($request->hasFile('logo_image')) {
            $validated['logo_image'] = $request->file('logo_image')->storeOnCloudinaryAs('giftcards/categories')->getSecurePath();
        }
        if ($request->hasFile('preview_image')) {
            $validated['preview_image'] = $request->file('preview_image')->storeOnCloudinaryAs('giftcards/categories/previews')->getSecurePath();
        }

        $category = GiftcardCategory::create($validated);
        return $this->success($category, 'Giftcard category created');
    }

    public function show(GiftcardCategory $giftcardCategory)
    {
        $giftcardCategory->loadCount('giftcards');
        return $this->success($giftcardCategory, 'Giftcard category');
    }

    public function update(Request $request, GiftcardCategory $giftcardCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'active' => 'sometimes|boolean',
            'logo_image' => 'sometimes',
            'preview_image' => 'sometimes',
        ]);

        if ($request->hasFile('logo_image')) {
            $validated['logo_image'] = $request->file('logo_image')->storeOnCloudinaryAs('giftcards/categories')->getSecurePath();
        }
        if ($request->hasFile('preview_image')) {
            $validated['preview_image'] = $request->file('preview_image')->storeOnCloudinaryAs('giftcards/categories/previews')->getSecurePath();
        }

        $giftcardCategory->update($validated);
        return $this->success($giftcardCategory, 'Giftcard category updated');
    }

    public function destroy(GiftcardCategory $giftcardCategory)
    {
        $giftcardCategory->delete();
        return $this->success([], 'Giftcard category deleted');
    }
}