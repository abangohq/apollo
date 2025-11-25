<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the app notifications
     */
    public function index(Request $request)
    {
        $notifs = UserRepository::notifications();
        return $this->success($notifs);
    }

    /**
     * Update the app notifications read receipt
     */
    public function read(Request $request, string $uid)
    {
        $notification = $request->user()->notifications()->whereId($uid)->firstOrFail();
        $notification->markAsRead();
        return $this->success(UserRepository::notification($notification), 'Notification marked as read');
    }

    /**
     * Mark all notifications as read receipt
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return $this->success([], 'Notifications marked as read');
    }

    /**
     * Delete notification entirely from db
     */
    public function destroy(Request $request, string $uid)
    {
        $notification = $request->user()->notifications()->whereid($uid)->firstOrFail();
        $notification->deleteOrFail();
        return $this->success(null, 'Notification deleted successfully');
    }
}
