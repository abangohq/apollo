<?php

namespace App\Repositories;

use App\Enums\Tranx;
use App\Models\AirtimeProduct;
use App\Models\AirtimeTopUp;
use App\Models\BettingProduct;
use App\Models\BettingTopUp;
use App\Models\CableProvider;
use App\Models\CableTopUp;
use App\Models\DataTopUp;
use App\Models\IspProvider;
use App\Models\MeterProduct;
use App\Models\MeterTopUp;
use App\Models\WifiProvider;
use App\Models\WifiTopUp;
use App\Services\Payment\RedbillerService;
use Illuminate\Support\Facades\Cache;

/**
 * @todo refactor users plans purchases 
 * into one single query builder
 */
class BillRepository
{
   public function __construct(public RedbillerService $billerService)
   {
      //
   }

   /**
    * Airtime status map
    */
   private function status(?string $val)
   {
      $value = strtolower($val);
      return match ($value) {
         'pending' => Tranx::TRANX_PENDING,
         'successful' => Tranx::TRANX_SUCCESS,
         'rejected' => Tranx::TRANX_REJECTED,
         'failed' => Tranx::TRANX_FAILED,
         default => null
      };
   }

   /**
    * Get both airtime and data isp provider
    */
   public function dataPlans(string $network)
   {
      return Cache::remember($network, now()->addMinutes(30), function () use ($network) {
         $response = $this->billerService->dataPlans(['product' => $network]);
         return $response?->details?->categories ?? [];
      });
   }

   /**
    * Get cable tv plans from biller
    */
   public function cablePlans(string $product)
   {
      $product = strtoupper($product);
      return Cache::remember($product, now()->addMinutes(30), function () use ($product) {
         $response = $this->billerService->cablePlans(['product' => $product]);
         return $response?->details?->categories ?? [];
      });
   }

   /**
    * Retrieve wifi plans to purchase
    */
   public function wifiPlans(string $product)
   {
      $product = strtoupper($product);
      return Cache::remember($product, now()->addMinutes(30), function () use ($product) {
         $response = $this->billerService->wifiPlans(['product' => $product]);
         return $response?->details?->categories ?? [];
      });
   }

   /**
    * Get all users airtime topups
    */
   public function usersAirtimeTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = AirtimeTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = AirtimeTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = AirtimeTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = AirtimeTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }

   /**
    * Get users betting topups
    */
   public function usersBetTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = BettingTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = BettingTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = BettingTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = BettingTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }

   /**
    * Get users cable topups
    */
   public function usersCableTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = CableTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = CableTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = CableTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = CableTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }

   /**
    * Get users data topup purchases
    */
   public function usersDataTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = DataTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = DataTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = DataTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = DataTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }

   /**
    * Get user wifi plans purchases
    */
   public function usersWifiTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = WifiTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = WifiTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = WifiTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = WifiTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }

   /**
    * Get users meter plans purchases
    */
   public function usersMeterTopups()
   {
      $request = request();
      $status = $this->status($request->status);

      $topups = MeterTopUp::query()->when($status, fn ($q) => $q->whereStatus($status))
         ->when($request->has('search'))->where(function ($q) use ($request) {
            $q->where('reference', 'LIKE', "{$request->search}%")
               ->orWhereHas('user', function ($q) use ($request) {
                  $q->where('username', 'LIKE', "{$request->search}%")
                     ->orWhere('email', 'LIKE', "{$request->search}%");
               });
         })
         ->orderBy('created_at', 'desc')
         ->with('user')->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = MeterTopUp::where('status', 'successful')->count();
      $overview['overview']['pending'] = MeterTopUp::where('status', 'pending')->count();
      $overview['overview']['rejected'] = MeterTopUp::where('status', 'rejected')->count();

      return array_merge($topups->toArray(), $overview);
   }
}
