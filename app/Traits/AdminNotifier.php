<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Admin\FlaggedTransactionNotice;

trait AdminNotifier
{
    /**
     * Notify active staff that a flagged transaction requires manual review
     */
    protected function notifyFlaggedAdmins(string $type, User $user, string $reference, int|float $amount): void
    {
        $staffs = User::staff()->active()->take(3)->get();
        if ($staffs->count() > 0) {
            rescue(fn () => Notification::send($staffs, new FlaggedTransactionNotice($type, $user, $reference, $amount)));
        }
    }
}