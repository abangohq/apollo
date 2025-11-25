<?php

namespace App\Repositories;

use App\Enums\Status;
use App\Models\Tier;
use App\Models\User;
use App\Support\Utils;
use Illuminate\Support\Facades\DB;

class UserRepository
{
   /**
    * Get the user app notifications to show
    */
   public static function notifications()
   {
      $notifications = request()->user()->notifications()->simplePaginate();

      $notifications->transform(function ($notification) {
         return [
            'id' => $notification->id,
            'title' => @$notification->data['title'] ?? null,
            'body' => @$notification->data['message'] ?? null,
            'created_at' => $notification->created_at,
         ];
      });

      return $notifications;
   }

   /**
    * Get the user app notifications to show
    */
   public static function notification($notification)
   {
      $notification = collect($notification);

      return [
         'id' => $notification['id'],
         'title' => @$notification['data']['title'] ?? null,
         'body' => @$notification['data']['message'] ?? null,
         'created_at' => $notification['created_at'],
      ];

      return $notification;
   }

   /**
    * Retrieve all users on system for dashboard
    */
   public function users()
   {
      $request = request();
      $tier = $request->integer('tier_id');
      $startDate = $request->date('from', 'd/m/Y') ?? now()->subMonth();
      $endDate = $request->date('to', 'd/m/Y') ?? now();
      $sortColumn = $request->input('orderBy');
      $sortDirection = Utils::userSortDirection($request->input('direction'));

      $query = User::query()
         ->select('users.*', 'wallets.balance as wallet_balance')
         ->addSelect(DB::raw("COALESCE((select SUM(usd_value) from crypto_transactions where user_id = users.id), 0) as total_trade"))
         ->leftJoin('wallets', 'wallets.user_id', 'users.id')
         ->whereUserType('user')
         ->when($request->has(['from', 'to']), function($q) use ($startDate, $endDate) {
            return $q->whereBetween('users.created_at', [$startDate, $endDate]);
         })
         ->when($tier > 0, function($q) use ($tier) {
            return $q->where('tier_id', $tier);
         })
         ->when($request->has('search'), function($q) use ($request) {
            return $q->where(function($query) use ($request) {
                  $query->where('email', 'LIKE', "%{$request->search}%")
                        ->orWhere('username', 'LIKE', "%{$request->search}%");
            });
         });

      // Handle specific sort columns
      if ($sortColumn === 'wallet_balance') {
         $query->orderBy('wallets.balance', $sortDirection);
      } elseif ($sortColumn === 'total_crypto_trade') {
         $query->orderBy('total_trade', $sortDirection);
      } else {
         $query->orderBy('users.created_at', $sortDirection);
      }

      $users = $query->paginate($request->per_page ? $request->per_page : 50);

      $users->through(function ($user) {
         $user->setAppends([]);
         return $user;
      });

      $overview['overview']['total'] = User::whereUserType('user')->whereNotNull('username')->count();
      $overview['overview']['deleted'] = User::whereNotNull('deleted_at')->count();
      $overview['overview']['suspended'] = User::whereUserType('user')->where('status', 'inactive')->count();
      $overview['overview']['active'] = User::whereUserType('user')->where('status', 'active')->whereNotNull('username')->count();

      $users = array_merge($users->toArray(), $overview);
      return $users;
   }

   /**
    * Get auth user tier information
    */
   public function getAuthUserTier()
   {
      $tiers = Tier::all();
      $userKyc = request()->user()->kyc;

      $tiers[0]['status'] = Status::COMPLETED->value;

      if ($userKyc->where('verification_type', 'nin')->count() > 0 || $userKyc->where('verification_type', 'bvn')->count() > 0) {
         $tiers[1]['status'] = $userKyc->where('verification_type', 'bvn')->value('status');
      } else {
         $tiers[1]['status'] = STATUS::IDLE->value;
      }

      return $tiers;
   }
}
