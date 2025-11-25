<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulletinRequest;
use App\Jobs\SendNewsletterJob;
use App\Jobs\User\UsersBulletin;
use App\Mail\Newsletter;
use App\Models\User;
use App\Services\Support\NewsletterService;
use Illuminate\Http\Request;
use App\Models\PushNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class PushNotificationController extends Controller
{
    /**
     *  Get all previous bulletin notifications
     */
    public function index(Request $request)
    {
        $notifications = PushNotification::latest()->paginate($request->per_page ? $request->per_page : 25);
        return $this->success($notifications, 'Notifications');
    }

    /**
     * Send new notifications to user group
     */
    public function bulletin(BulletinRequest $request)
    {
        $notif = PushNotification::create($request->bulletinAttributes());
        UsersBulletin::dispatch($request->validated(), $notif);

        return $this->success($notif);
    }

    public function sendNewsletter(Request $request)
    {
        $newsletterService = new NewsletterService();

        $data = $request->all();

        $payload = [
            'target' => $request->input('target', 'all_users'),
            'data' => $data,
        ];
        
        $newsletterService->handle($payload);

        return $this->success($data, 'Successfully sent newsletter');
    }
}
