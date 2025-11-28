<?php

namespace App\Services\Support;

use App\Models\PushNotification;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;

class BulletinService
{
   public $messaging;

   public function __construct(public array $bulletin, public PushNotification $notification)
   {
      $this->messaging = app('firebase.messaging');
   }

   /**
    * handle bulletin action type
    */
   public function handle($payload)
   {
      // all_users,active_users,inactive_users,recent_users,specific_user
      switch ($payload['target']) {
         case 'all_users':
            $this->allUsers();
            break;
         case 'active_users':
            $this->activeUsers();
            break;
         case 'inactive_users':
            $this->inactiveUsers();
            break;
         case 'recent_users':
            $this->recentUsers();
            break;
         case 'specific_user':
            $this->specificUser($payload['user']);
            break;

         default:
            //
            break;
      }
   }

   /**
    * Bulletin to all users in app
    */
   public function allUsers()
   {
      $success = 0; $failed = 0;
      User::query()->chunkById(100, function ($users) use (&$success, &$failed) {
         $tokens = $users->pluck('device_token')->filter()->toArray();
         if (empty($tokens)) return;

         $message = CloudMessage::new()->withNotification($this->bulletin);
         $sendReport = $this->messaging->sendMulticast($message, $tokens);
         $success += $sendReport->successes()->count();
         $failed += $sendReport->failures()->count();
      });
      $this->notification->update(["successful" => $success, "failed" => $failed]);
   }

   /**
    * Active users bulletin notification
    */
   public function activeUsers()
   {
      $success = 0; $failed = 0;
      User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
         ->where('crypto_transactions.created_at', '>', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) use (&$success, &$failed) {
            $tokens = $users->pluck('device_token')->filter()->toArray();
            if (empty($tokens)) return;

            $message = CloudMessage::new()->withNotification($this->bulletin);
            $sendReport = $this->messaging->sendMulticast($message, $tokens);
            $success += $sendReport->successes()->count();
            $failed += $sendReport->failures()->count();
         });
      $this->notification->update(["successful" => $success, "failed" => $failed]);
   }

   /**
    * Inactive users bulletin notification
    */
   public function inactiveUsers()
   {
      $success = 0; $failed = 0;
      User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
         ->where('crypto_transactions.created_at', '<', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) use (&$success, &$failed) {
            $tokens = $users->pluck('device_token')->filter()->toArray();
            if (empty($tokens)) return;

            $message = CloudMessage::new()->withNotification($this->bulletin);
            $sendReport = $this->messaging->sendMulticast($message, $tokens);
            $success += $sendReport->successes()->count();
            $failed += $sendReport->failures()->count();
         });
      $this->notification->update(["successful" => $success, "failed" => $failed]);
   }

   /**
    * Recent users bulletin notification
    */
   public function recentUsers()
   {
      $success = 0; $failed = 0;
      User::where('created_at', '>', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) use (&$success, &$failed) {
            $tokens = $users->pluck('device_token')->filter()->toArray();
            if (empty($tokens)) return;

            $message = CloudMessage::new()->withNotification($this->bulletin);
            $sendReport = $this->messaging->sendMulticast($message, $tokens);
            $success += $sendReport->successes()->count();
            $failed += $sendReport->failures()->count();
         });
      $this->notification->update(["successful" => $success, "failed" => $failed]);
   }

   /**
    * Specifc users bulletin notification
    */
   public function specificUser(mixed $userMail)
   {
      $token = User::query()->where('email', $userMail)->value('device_token');

      if (empty($token)) {
         $this->notification->increment('failed');
         return;
      }

      $message = CloudMessage::withTarget('token', $token)->withNotification($this->bulletin);

      $this->messaging->send($message);
      $this->notification->increment('successful');
   }
}
