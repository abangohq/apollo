<?php

namespace App\Repositories;

use App\Models\CryptoAsset;
use App\Models\CryptoRate;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\SwapTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class CryptoRepository
{
   /**
    * Retrieve crypto wallet for an assets
    */
   public function wallet(CryptoAsset $asset)
   {
      $wallet = request()->user()->cryptowallets()->where('crypto_asset_id', $asset->id)->first();

      if (!$wallet) {
         abort(409, "You don't have an address, kindly create one");
      }

      $wallet->setAttribute('symbol', $asset->symbol);
      return $wallet;
   }

   /**
    * Get available crypto assets
    */
   public function assets(bool $graph)
   {
      $cryptos = CryptoAsset::active()->get()
         ->when(!$graph)->makeHidden(['price_graph_data_points'])
         ->when(request()->route()->getName() == 'front.crypto.asset')->makeHidden([
            'percent_change_1hr',
            'percent_change_24hr',
            'latest_quote',
            'market_cap',
            'total_supply',
            'volume',
            'circulating_supply',
            'term',
            'price'
         ]);

      $cryptos->each->currency = "USD";
      return $cryptos;
   }

   /**
    * Get crypto conversion rate
    */
   public function rates()
   {
      return CryptoRate::published()->oldest('range_start')->get();
   }

   /**
    * Get crypto wallet for a cypto chain
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
   public function chainWallet(mixed $userId, string $chain)
   {
      return CryptoWallet::query()
         ->where('user_id', $userId)
         ->where('chain', $chain)->with('user')->first();
   }

   /**
    * Get the crypto rate range to use for exchange
    */
   public function rateRange(string $usdAmount)
   {
      $usdAmount = floor($usdAmount);

      return CryptoRate::query()->where('range_start', '<=', $usdAmount)
         ->where('range_end', '>=', $usdAmount)->first();
   }

   /**
    * Retrieve users crypto transaction deposits
    */
    public function usersTransactions()
    {
        $request = request();
        /** @var LengthAwarePaginator $deposits */
        $deposits = CryptoTransaction::query()
            ->when($request->has(['from', 'to']), function ($q) use ($request) {
                $q->whereBetween('created_at', [$request->from, $request->to]);
            })
            ->when($request->has('coin') || $request->has('crypto'), function ($q) use ($request) {
                $q->where('crypto', $request->coin ?? $request->crypto);
            })
            ->when($request->has('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->has('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('reference', 'LIKE', "{$request->search}%")
                          ->orWhere('transaction_hash', 'LIKE', "{$request->search}%");
                });
            })
            ->with('user')
            ->latest()
            ->paginate($request->per_page ?: 25);
    
        // Add the `first_trade` attribute to each transaction
        $deposits->getCollection()->transform(function ($transaction) {
            $firstTransaction = CryptoTransaction::where('user_id', $transaction->user_id)
                ->orderBy('created_at')
                ->value('id');
    
            $transaction->first_trade = $transaction->id === $firstTransaction;
            return $transaction;
        });
    
        $overview['overview']['successful'] = CryptoTransaction::where('status', 'successful')->count();
        $overview['overview']['pending'] = CryptoTransaction::where('status', 'pending')->count();
    
        return array_merge($deposits->toArray(), $overview);
    }
    

   /**
    * Retrieve crypto swap transactions
    */
   public function swapTransactions()
   {
      $request = request();
      $swaps = SwapTransaction::query()
         ->when($request->has(['from', 'to']))->whereBetween('created_at', [$request->from, $request->to])
         ->when($request->has('status'))->where('status', $request->status)
         ->when($request->has('search'), fn($q) => $q->where('reference', 'LIKE', "{$request->search}%"))
         ->with('user')->latest()
         ->paginate($request->per_page ? $request->per_page : 25);

      $overview['overview']['successful'] = CryptoTransaction::where('status', 'finished')->count();
      $overview['overview']['pending'] = CryptoTransaction::whereIn('status', ['new', 'confirming', 'exchanging', 'sending'])->count();

      return array_merge($swaps->toArray(), $overview);
   }

   /**
    * Get user swap transactions
    */
   public function userSwapTransactions(int|string $userId)
   {
      $request = request();
      return SwapTransaction::query()
         ->when($request->has(['from', 'to']))->whereBetween('created_at', [$request->from, $request->to])
         ->when($request->has('status'))->where('status', $request->status)
         ->when($request->has('search'), fn($q) => $q->where('reference', 'LIKE', "{$request->search}%"))
         ->where('user_id', $userId)
         ->latest()
         ->paginate($request->per_page ? $request->per_page : 25);
   }

   /**
    * Retrieve users crypto transaction deposits
    */
   public function userTransactions(int|string $userId)
   {
      $request = request();
      return CryptoTransaction::query()
         ->when($request->has(['from', 'to']))->whereBetween('created_at', [$request->from, $request->to])
         ->when($request->has('status'))->where('status', $request->status)
         ->when($request->has('search'), fn($q) => $q->where('reference', 'LIKE', "{$request->search}%"))
         ->where('user_id', $userId)
         ->latest()
         ->paginate($request->per_page ? $request->per_page : 25);
   }
}
