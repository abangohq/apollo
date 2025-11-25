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
      User::query()->chunkById(100, function ($users) {
         $tokens = $users->pluck('device_token')->filter()->toArray();

         $message = CloudMessage::new()->withNotification($this->bulletin);

         $sendReport = $this->messaging->sendMulticast($message, $tokens);

         $this->notification->update([
            "successful" => $sendReport->successes()->count(),
            "failed" => $sendReport->failures()->count(),
         ]);
      });
   }

   /**
    * Active users bulletin notification
    */
   public function activeUsers()
   {
      User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
         ->where('crypto_transactions.created_at', '>', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) {
            $tokens = $users->pluck('device_token')->filter()->toArray();

            $message = CloudMessage::new()->withNotification($this->bulletin);

            $sendReport = $this->messaging->sendMulticast($message, $tokens);

            $this->notification->update([
               "successful" => $sendReport->successes()->count(),
               "failed" => $sendReport->failures()->count(),
            ]);
         });
   }

   /**
    * Inactive users bulletin notification
    */
   public function inactiveUsers()
   {
      User::join('crypto_transactions', 'users.id', 'crypto_transactions.user_id')
         ->where('crypto_transactions.created_at', '<', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) {
            $tokens = $users->pluck('device_token')->filter()->toArray();

            $message = CloudMessage::new()->withNotification($this->bulletin);

            $sendReport = $this->messaging->sendMulticast($message, $tokens);

            $this->notification->update([
               "successful" => $sendReport->successes()->count(),
               "failed" => $sendReport->failures()->count(),
            ]);
         });
   }

   /**
    * Recent users bulletin notification
    */
   public function recentUsers()
   {
      User::where('created_at', '>', now()->subDays(30)->endOfDay())
         ->chunkById(100, function ($users) {
            $tokens = $users->pluck('device_token')->filter()->toArray();

            $message = CloudMessage::new()->withNotification($this->bulletin);

            $sendReport = $this->messaging->sendMulticast($message, $tokens);

            $this->notification->update([
               "successful" => $sendReport->successes()->count(),
               "failed" => $sendReport->failures()->count(),
            ]);
         });
   }

   /**
    * Specifc users bulletin notification
    */
   public function specificUser(mixed $userMail)
   {
      $token = User::query()->where('email', $userMail)->value('device_token');

      if (empty($token)) {
         return;
      }

      $message = CloudMessage::withTarget('token', $token)->withNotification($this->bulletin);

      $sendReport = $this->messaging->send($message);

      $this->notification->update([
         "successful" => $sendReport->successes()->count(),
         "failed" => $sendReport->failures()->count(),
      ]);
   }
}
