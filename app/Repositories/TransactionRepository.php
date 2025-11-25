<?php

namespace App\Repositories;

use App\Enums\Tranx;
use App\Models\Reconciliation;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Support\Utils;

class TransactionRepository
{
   /**
    * Get wallet transactions<withdrawal bills>
    */
   public static function wallet($userId)
   {
      $request = request();
      $bills = ['data', 'airtime', 'meter', 'betting', 'cable', 'wifi'];
      $statuses = Utils::tranxStatus();

      return WalletTransaction::whereNotNull('transaction_id')
         ->where('user_id', $userId)->where('transaction_type', '!=', Tranx::RECONCILE)->where('transaction_type', '!=', Tranx::BONUS)
         ->when(in_array($request->status, $statuses))->whereHasMorph(
            'transactable',
            Utils::tranxMorphModels(),
            function ($q) use ($request) {
               $q->where('status', $request->status);
            }
         )
         ->when(strtolower($request->type) == 'bills')->whereIn('transaction_type', $bills)
         ->when(strtolower($request->type) == 'crypto', fn($q) => $q->whereIn('transaction_type', ['crypto','swap']))
         ->when(strtolower($request->type) == 'withdrawal')->where('transaction_type', 'withdraw')
         ->with('transactable')
         ->latest()
         ->paginate();
   }

   /**
    * Check for existing reversal on the topup
    */
   public static function hasReversal(mixed $transactionId, Tranx $type)
   {
      return WalletTransaction::where('transaction_id', $transactionId)
         ->where('transaction_type', $type->value)
         ->where('is_reversal', true)->exists();
   }

   /**
    * Get withdrawals with transactions
    */
   public static function withdraws()
   {
      $request = request();
      $status = Utils::withdrawalStatus($request->status);

      $withdrawals = Withdrawal::query()->when($status)->where('status', $status)
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "%{$request->search}%")
               ->orWhere('account_name', 'LIKE', "%{$request->search}%");
         })
         ->with([
            'user' => fn($q) => $q->withTrashed()->select('id', 'name', 'username', 'email'),
            'staff' =>  fn($q) => $q->select('id', 'name', 'username', 'email'),
            'rejectionReason'
         ])
         ->orderBy('created_at', 'desc')
         ->paginate();

      $withdrawals = $withdrawals->through(function ($withdrawal) {
         $withdrawal->user?->setAppends([]);
         $withdrawal->staff?->setAppends([]);

         return $withdrawal;
      });

      $overview['overview']['successful'] = Withdrawal::where('status', 'successful')->count();
      $overview['overview']['pending'] = Withdrawal::where('status', 'pending')->count();
      $overview['overview']['rejected'] = Withdrawal::where('status', 'rejected')->count();

      $withdrawals = array_merge($withdrawals->toArray(), $overview);

      return $withdrawals;
   }

   /**
    * Get withdrawals with transactions
    */
   public static function userWithdrawals(int|string $userId)
   {
      $request = request();
      $status = Utils::withdrawalStatus($request->status);
      $withdrawals = Withdrawal::query()->when($status)->where('status', $status)
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "%{$request->search}%")
               ->orWhere('account_name', 'LIKE', "%{$request->search}%");
         })
         ->with([
            'staff' =>  fn($q) => $q->select('id', 'name', 'username', 'email'),
            'rejectionReason'
         ])
         ->orderBy('created_at', 'desc')
         ->where('user_id', $userId)
         ->paginate();

         $withdrawals = $withdrawals->through(function ($withdrawal) {
         $withdrawal->user?->setAppends([]);
         $withdrawal->staff?->setAppends([]);

         return $withdrawal;
      });

      return $withdrawals;
   }

   /**
    * Get withdrawals settled by an employee
    */
   public static function settleByEmployee($userId)
   {
      return Withdrawal::where('settled_by', $userId)
         ->orderBy('created_at', 'desc')
         ->with(['user', 'rejectionReason', 'settled_by'])
         ->paginate(25);
   }

   /**
    * Retrieve reconciliation transactions
    */
   public static function reconciliations()
   {
      $reconciles = Reconciliation::with([
         'transaction',
         'originTransaction',
         'user' => fn($q) => $q->withTrashed()->select('id', 'name', 'username', 'email'),
         'staff' => fn($q) => $q->select('id', 'name', 'username', 'email'),
      ])
      ->orderBy('created_at', 'desc')
      ->paginate();

      $reconciles->each(function ($reconcile) {
         $reconcile->user?->setAppends([]);
         $reconcile->staff?->setAppends([]);
      });

      return $reconciles;
   }

   /**
    * Retrieve reconciliable transactions transactions
    */
   public static function reconciliableTransaction()
   {
      $request = request();

      return WalletTransaction::whereNotNull('transaction_id')
         ->when($request->filled('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhere('amount', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($qry) use ($request) {
                  $qry->where('name', 'LIKE', "{$request->search}%");
                  $qry->orWhere('username', 'LIKE', "{$request->search}%");
                  $qry->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->take(25)
         ->with(['user' => fn($q) => $q->select('id', 'name', 'email', 'username')])
         ->get();
   }
}
