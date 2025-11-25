<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use Illuminate\Http\Request;

class AppBannerController extends Controller
{
    public function index()
    {
        return $this->success(AppBanner::all(), 'App Banners');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'banner_image' => 'required|image|max:2048',
            'url' => 'nullable|string|max:255',
        ]);

        $filename = $request->banner_image->storeOnCloudinaryAs('banners')->getSecurePath();
        $validated['banner_image'] = $filename;

        $appBanner = AppBanner::create($validated);

        return $this->success($appBanner, 'App Banner created successfully');
    }

    public function show(AppBanner $appBanner)
    {
        return $this->success($appBanner, 'App Banner');
    }

    public function update(Request $request, AppBanner $appBanner)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'banner_image' => 'sometimes|string|max:255',
            'url' => 'sometimes|string|max:255',
        ]);

        $appBanner->update($validated);

        return $this->success($appBanner, 'App Banner updated successfully');
    }

    public function destroy(AppBanner $appBanner)
    {
        $appBanner->delete();

        return $this->success([], 'App Banner deleted successfully');
    }
}
