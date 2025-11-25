<?php

namespace App\Services\Support;

use App\Models\CryptoTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Payment\RedbillerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\CryptoAsset;
use App\Services\Crypto\CoinGeckoService;

class AnalyticsService
{
   public function __construct(public RedbillerService $redbiller)
   {
      //
   }

   /**
    * The dashboard overview statistics.
    */
   public function overview(?string $scope = null)
   {
      $data = [];

      $currentDate = now();
      $previousMonth = now()->subMonth();

      // If a scope is provided, compute only the requested section(s)
      if (is_string($scope) && strlen($scope) > 0) {
         $normalized = strtolower(trim($scope));
         switch ($normalized) {
            case 'today':
               $this->todayUsersStats($data, $currentDate);
               $this->todayCryptoStats($data, $currentDate, $previousMonth);
               $this->todayWithdrawalStats($data, $currentDate);
               break;
            case 'yesterday':
               $this->yesterdayUsersStats($data, $currentDate);
               $this->yesterdayCryptoStats($data, $currentDate);
               $this->yesterdayWithdrawalStats($data, $currentDate);
               break;
            case 'week':
               $this->weeklyUsersStats($data, $currentDate);
               $this->weeklyCryptoStats($data, $currentDate);
               $this->weeklyWithdrawalStats($data, $currentDate);
               break;
            case 'month':
               $this->monthlyUsersStats($data, $currentDate);
               $this->monthlyCryptoStats($data, $currentDate, $previousMonth);
               $this->monthlyWithdrawalStats($data, $currentDate);
               break;
            case 'all_time':
               $this->allTimeUsersStats($data);
               $this->allCryptoStats($data);
               $this->allTimeWithdrawalStats($data);
               break;
            default:
               // Unknown scope: fall back to full overview
               $scope = null;
               break;
         }
      }

      // Full overview when scope is null or invalid
      if ($scope === null) {
         $this->userStats($data, $currentDate, $previousMonth)
            ->monthlyRegistrationChart($data)
            ->allCryptoStats($data)
            ->monthlyCryptoStats($data, $currentDate, $previousMonth)
            ->todayCryptoStats($data, $currentDate, $previousMonth)
            ->yesterdayCryptoStats($data, $currentDate)
            ->weeklyCryptoStats($data, $currentDate)
            ->withdrawalStats($data, $currentDate, $previousMonth);
      }

      $data['wallet_balance'] = Wallet::sum('balance');
      $data['redbiller_balance'] = $this->redbillerBalance();
      $this->monthlyRegistrationChart($data);

      return $data;
   }

   /**
    * Retrieve redbiller wallet balance
    */
   public function redbillerBalance()
   {
      return rescue(function () {
         return Cache::remember('redbiller_balance', now()->addMinutes(5), function () {
            $response = $this->redbiller->balance();
            return data_get($response, 'details');
         });
      }, null, true);
   }

   /**
    * Retrieve users stats monthly|daily|all-time
    */
   public function userStats(&$data, $currentDate, $previousMonth)
   {
      // All-time user statistics
      $data['all_time']['users'] = [
         'total' => User::whereUserType('user')->whereNotNull('username')->count(),
         'deleted' => User::whereUserType('user')->whereNotNull('deleted_at')->count(),
         'suspended' => User::whereUserType('user')->where('status', 'inactive')->count(),
         'active' => User::whereUserType('user')->where('status', 'active')->count(),
      ];

      // Monthly user registration statistics
      $data['month']['users'] = [
         // Current month registrations
         'registrations' => User::whereUserType('user')
            ->whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)
            ->count(),
         // Previous month for comparison
         'previous' => User::whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $previousMonth->month)->count(),
         // Alias of current month for consumers that expect this key
         'current' => User::whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)->count(),
      ];

      // Daily user registration statistics
      $data['today']['users'] = [
         'registrations' => User::whereUserType('user')
            ->whereDate('created_at', $currentDate)
            ->count(),
         'previous' => User::whereDate('created_at', $currentDate->copy()->subDay())->count(),
         'current' => User::whereDate('created_at', $currentDate)->count()
      ];

      return $this;
   }

   /**
    * Scoped users stats: today only
    */
   public function todayUsersStats(&$data, $currentDate)
   {
      $data['today']['users'] = [
         'registrations' => User::whereUserType('user')
            ->whereDate('created_at', $currentDate)
            ->count(),
      ];
   }

   /**
    * Scoped users stats: yesterday only
    */
   public function yesterdayUsersStats(&$data, $currentDate)
   {
      $yesterday = $currentDate->copy()->subDay();
      $data['yesterday']['users'] = [
         'registrations' => User::whereUserType('user')
            ->whereDate('created_at', $yesterday)
            ->count(),
      ];
   }

   /**
    * Scoped users stats: current week only
    */
   public function weeklyUsersStats(&$data, $currentDate)
   {
      $start = $currentDate->copy()->startOfWeek();
      $end = $currentDate->copy()->endOfWeek();
      $data['week']['users'] = [
         'registrations' => User::whereUserType('user')
            ->whereBetween('created_at', [$start, $end])
            ->count(),
      ];
   }

   /**
    * Scoped users stats: current month only
    */
   public function monthlyUsersStats(&$data, $currentDate)
   {
      $data['month']['users'] = [
         'registrations' => User::whereUserType('user')
            ->whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)
            ->count(),
      ];
   }

   /**
    * Retrieve yesterday crypto statistics
    */
   public function yesterdayCryptoStats(&$data, $currentDate)
   {
      $yesterday = $currentDate->copy()->subDay();

      $data['yesterday']['crypto'] = [
         'total' => CryptoTransaction::whereDate('created_at', $yesterday)->count(),
      ];

      $data['yesterday']['crypto']['volume'] = (float) CryptoTransaction::whereDate('created_at', $yesterday)
         ->where('status', 'successful')
         ->sum('crypto_amount');

      $data['yesterday']['crypto']['amount'] = CryptoTransaction::selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereDate('created_at', $yesterday)
         ->where('status', 'successful')
         ->first();

      $cryptoAssets = CryptoTransaction::select('crypto')
         ->selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereDate('created_at', $yesterday)
         ->where('status', 'successful')
         ->groupBy('crypto')
         ->get();

      $data['yesterday']['crypto']['asset'] = [];

      $cryptoAssets->each(function ($asset) use (&$data) {
         $data['yesterday']['crypto']['asset'][$asset->crypto] = [
            'naira' => $asset->naira,
            'usd' => $asset->usd,
         ];
      });

      return $this;
   }

   /**
    * Retrieve this week crypto statistics (startOfWeek to endOfWeek)
    */
   public function weeklyCryptoStats(&$data, $currentDate)
   {
      $startOfWeek = $currentDate->copy()->startOfWeek();
      $endOfWeek = $currentDate->copy()->endOfWeek();

      $data['week']['crypto'] = [
         'total' => CryptoTransaction::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
      ];

      $data['week']['crypto']['volume'] = (float) CryptoTransaction::whereBetween('created_at', [$startOfWeek, $endOfWeek])
         ->where('status', 'successful')
         ->sum('crypto_amount');

      $data['week']['crypto']['amount'] = CryptoTransaction::selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
         ->where('status', 'successful')
         ->first();

      $cryptoAssets = CryptoTransaction::select('crypto')
         ->selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
         ->where('status', 'successful')
         ->groupBy('crypto')
         ->get();

      $data['week']['crypto']['asset'] = [];

      $cryptoAssets->each(function ($asset) use (&$data) {
         $data['week']['crypto']['asset'][$asset->crypto] = [
            'naira' => $asset->naira,
            'usd' => $asset->usd,
         ];
      });

      return $this;
   }

   /**
    * Retrieve all time crypto statistic
    */
   public function allCryptoStats(&$data)
   {
      $data['all_time']['crypto'] = [
         'total' => CryptoTransaction::count(),
      ];

      $allTime = CryptoTransaction::selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->where('status', 'successful')->first();

      $data['all_time']['crypto']['amount'] = $allTime;

      $cryptoAssets = CryptoTransaction::select('crypto')
         ->selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->where('status', 'successful')
         ->groupBy('crypto')->get();

      $cryptoAssets->each(function ($asset) use (&$data) {
         $data['all_time']['crypto']['asset'][$asset->crypto] = [
            'naira' => $asset->naira,
            'usd' => $asset->usd,
         ];
      });

      return $this;
   }

   /**
    * Retrieve monthly crypto statistics
    */
   public function monthlyCryptoStats(&$data, $currentDate, $previousMonth)
   {
      // Use current calendar month only
      $data['month']['crypto'] = [
         'total' => CryptoTransaction::whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)->count()
      ];

      $data['month']['crypto']['amount'] = CryptoTransaction::selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereYear('created_at', $currentDate->year)
         ->whereMonth('created_at', $currentDate->month)
         ->where('status', 'successful')
         ->first();

      $cryptoAssets = CryptoTransaction::select('crypto')
         ->selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd, SUM(crypto_amount) as volume')
         ->whereYear('created_at', $currentDate->year)
         ->whereMonth('created_at', $currentDate->month)
         ->where('status', 'successful')
         ->groupBy('crypto')
         ->get();

      $cryptoAssets->each(function ($asset) use (&$data) {
         $data['month']['crypto']['asset'][$asset->crypto] = [
            'naira' => $asset->naira,
            'usd' => $asset->usd,
            'volume' => $asset->volume,
         ];
      });

      return $this;
   }

   /**
    * Collect crypto transaction statistics
    */
   public function todayCryptoStats(&$data, $currentDate, $previousMonth)
   {
      $data['today']['crypto'] = [
         'total' => CryptoTransaction::whereDate('created_at', $currentDate)->count(),
      ];

      // Daily trade volume (sum of crypto_amount for successful transactions)
      $data['today']['crypto']['volume'] = (float) CryptoTransaction::whereDate('created_at', $currentDate)
         ->where('status', 'successful')
         ->sum('crypto_amount');

      $data['today']['crypto']['amount'] = CryptoTransaction::selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereDate('created_at', $currentDate)
         ->where('status', 'successful')->first();

      $cryptoAssets = CryptoTransaction::select('crypto')
         ->selectRaw('SUM(payout_amount) as naira, SUM(usd_value) as usd')
         ->whereDate('created_at', $currentDate)
         ->where('status', 'successful')->groupBy('crypto')->get();

      $data['today']['crypto']['asset'] = [];

      $cryptoAssets->each(function ($asset) use (&$data) {
         $data['today']['crypto']['asset'][$asset->crypto] = [
            'naira' => $asset->naira,
            'usd' => $asset->usd,
         ];
      });

      return $this;
   }

   /**
    * Retrieve withdrawal statistics
    */
   public function withdrawalStats(&$data, $currentDate, $previousMonth)
   {
      $data['all_time']['withdrawals'] = [
         'count' => Withdrawal::count(),
         'amount' => (float) Withdrawal::where('status', 'successful')->sum('amount'),
      ];

      // Current calendar month only
      $data['month']['withdrawals'] = [
         'count' => Withdrawal::whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)->count(),

         'amount' => (float) Withdrawal::where('status', 'successful')
            ->whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)
            ->sum('amount'),
      ];

      $data['today']['withdrawals'] = [
         'count' => Withdrawal::whereDate('created_at', $currentDate)->count(),
         'amount' => Withdrawal::where('status', 'successful')
            ->whereDate('created_at', $currentDate)->sum('amount'),
      ];

      return $this;
   }

   /**
    * Scoped withdrawals: today only
    */
   public function todayWithdrawalStats(&$data, $currentDate)
   {
      $data['today']['withdrawals'] = [
         'count' => Withdrawal::whereDate('created_at', $currentDate)->count(),
         'amount' => (float) Withdrawal::where('status', 'successful')
            ->whereDate('created_at', $currentDate)->sum('amount'),
      ];
   }

   /**
    * Scoped withdrawals: yesterday only
    */
   public function yesterdayWithdrawalStats(&$data, $currentDate)
   {
      $yesterday = $currentDate->copy()->subDay();
      $data['yesterday']['withdrawals'] = [
         'count' => Withdrawal::whereDate('created_at', $yesterday)->count(),
         'amount' => (float) Withdrawal::where('status', 'successful')
            ->whereDate('created_at', $yesterday)->sum('amount'),
      ];
   }

   /**
    * Scoped withdrawals: current week only
    */
   public function weeklyWithdrawalStats(&$data, $currentDate)
   {
      $start = $currentDate->copy()->startOfWeek();
      $end = $currentDate->copy()->endOfWeek();
      $data['week']['withdrawals'] = [
         'count' => Withdrawal::whereBetween('created_at', [$start, $end])->count(),
         'amount' => (float) Withdrawal::where('status', 'successful')
            ->whereBetween('created_at', [$start, $end])->sum('amount'),
      ];
   }

   /**
    * Scoped withdrawals: current month only
    */
   public function monthlyWithdrawalStats(&$data, $currentDate)
   {
      $data['month']['withdrawals'] = [
         'count' => Withdrawal::whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)->count(),
         'amount' => (float) Withdrawal::where('status', 'successful')
            ->whereYear('created_at', $currentDate->year)
            ->whereMonth('created_at', $currentDate->month)->sum('amount'),
      ];
   }

   /**
    * Scoped withdrawals: all time only
    */
   public function allTimeWithdrawalStats(&$data)
   {
      $data['all_time']['withdrawals'] = [
         'count' => Withdrawal::count(),
         'amount' => (float) Withdrawal::where('status', 'successful')->sum('amount'),
      ];
   }

   /**
    * Scoped users: all time only
    */
   public function allTimeUsersStats(&$data)
   {
      $data['all_time']['users'] = [
         'total' => User::whereUserType('user')->whereNotNull('username')->count(),
         'deleted' => User::whereUserType('user')->whereNotNull('deleted_at')->count(),
         'suspended' => User::whereUserType('user')->where('status', 'inactive')->count(),
         'active' => User::whereUserType('user')->where('status', 'active')->count(),
      ];
   }

   /**
    * Monthly registration bar chart data (Jan 2025 - Dec 2025)
    */
   public function monthlyRegistrationChart(&$data)
   {
      $year = 2025;
      $months = [];

      for ($month = 1; $month <= 12; $month++) {
         $registrations = User::whereUserType('user')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

         $months[] = [
            'month' => date('M', mktime(0, 0, 0, $month, 1)),
            'month_number' => $month,
            'registrations' => $registrations,
            'year' => $year
         ];
      }

      $data['chart']['monthly_registrations'] = $months;

      return $this;
   }

   /**
    * Resolve USD price for a ticker using CryptoAsset or CoinGecko fallback
    */
   protected function usdPriceForTicker(string $ticker): float
   {
      $symbol = strtoupper($ticker);

      // Handle USDT family explicitly
      if ($symbol === 'USDT') {
         $asset = CryptoAsset::where('symbol', 'LIKE', 'USDT%')->first();
         if ($asset && is_numeric($asset->price)) {
            return (float) $asset->price;
         }
         return 1.0; // reasonable default for USDT
      }

      // Try exact symbol match from stored assets
      $asset = CryptoAsset::where('symbol', $symbol)->first();
      if ($asset && is_numeric($asset->price) && (float) $asset->price > 0) {
         return (float) $asset->price;
      }

      // Fallback to CoinGecko mapping
      $map = [
         'BTC' => 'bitcoin',
         'ETH' => 'ethereum',
         'SOL' => 'solana',
         'LTC' => 'litecoin',
         'XRP' => 'ripple',
         'ADA' => 'cardano',
         'USDC' => 'usd-coin',
         'TRX' => 'tron',
         'DOGE' => 'dogecoin',
      ];

      $id = $map[$symbol] ?? strtolower($symbol);

      try {
         $service = app(CoinGeckoService::class);
         $price = (float) $service->cryptoPrice($id);
         return $price > 0 ? $price : 0.0;
      } catch (\Throwable $e) {
         return 0.0;
      }
   }

   /**
    * Total withdrawal performed statistics
    */
   public function totalWithdrawals()
   {
      $withdrawals = [];
      $withdrawals['count'] = Withdrawal::count();
      $withdrawals['amount'] = Withdrawal::sum('amount');
      $withdrawals['successful'] = Withdrawal::where('status', 1)->count();
      $withdrawals['pending'] = Withdrawal::where('status', 0)->count();
      $withdrawals['rejected'] = Withdrawal::where('status', 2)->count();

      return $withdrawals;
   }


   /**
    * Get the top seller for crypto
    */
   public function topSeller()
   {
      $sellers = User::all();

      $sellers = $sellers->sortByDesc(function ($seller) {
         return $seller->total_transactions;
      });

      $sellers = $sellers->values()->take(5);
   }
}
