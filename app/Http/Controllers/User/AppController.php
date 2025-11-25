<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\SupportEmail;
use App\Models\AppBanner;
use App\Models\SystemStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AppController extends Controller
{
    public function sendFeedback(Request $request){
        $data = $request->all();
        Mail::to(env('MAIL_FROM_ADDRESS'))->queue(new SupportEmail($data));

        return $this->success([], 'Successfully sent your message');
    }

    public function getAppBanners()
    {
        return $this->success(AppBanner::all(), 'App Banners');
    }

    /**
     * Retrive the system status
     */
    public function systemStatus(Request $request)
    {
        $settings = SystemStatus::all();

        return $this->success($settings, 'System status');
    }
}
