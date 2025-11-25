<?php

namespace App\Actions\Payment;

use App\Http\Requests\Crypto\CreateWalletRequest;
use App\Models\CryptoAsset;
use App\Services\Crypto\VaultodyService;
use App\Services\Crypto\XprocessingService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CryptoWalletAction
{
   use WalletEntity;

   public function __construct(
      public CreateWalletRequest $request,
      public VaultodyService $vaultody,
      public XprocessingService $xprocess
   ) {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $asset = $this->asset($this->request->symbol);

      try {
          if (in_array($this->request->symbol, ['BTC', 'ETH', 'USDT (ERC20)', 'USDT (TRC20)', 'SOL', 'XRP'])) {
            return $this->vaultody->createWallet($asset, request()->user());
         }

         return $this->xprocess->createWallet($asset, request()->user());
      } catch (\Throwable $th) {
         Log::debug($th);
         abort(409, 'Unable to create your wallet address please try again.');
      }
   }

   /**
    * Run validation on the address creation
    */
   public function asset($crypto)
   {
      $asset = CryptoAsset::whereSymbol($crypto)->firstOrFail();

      $hasWallet = request()->user()->cryptowallets()->where('crypto_asset_id',  $asset->id)->exists();

      if ($hasWallet) {
         throw ValidationException::withMessages(['symbol' => ['You have a wallet already.']]);
      }

      return $asset;
   }
}
