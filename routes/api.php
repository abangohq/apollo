<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetsController;
use App\Http\Controllers\BybitController;
use App\Http\Controllers\User\AppController;
use App\Http\Controllers\User\BankController;
use App\Http\Controllers\User\BettingController;
use App\Http\Controllers\User\CableController;
use App\Http\Controllers\User\CryptoController;
use App\Http\Controllers\User\CryptoSwapController;
use App\Http\Controllers\User\GiftcardCategoryController;
use App\Http\Controllers\User\GiftcardController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\MeterController;
use App\Http\Controllers\User\MobileBillController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\PinController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\TradeController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\WifiController;
use App\Http\Controllers\User\ReferralController;
use App\Http\Controllers\User\SignUpBonusController;
use App\Http\Controllers\Api\IntercomController;
use App\Http\Controllers\Webhook\CrpytoController;
use App\Http\Controllers\Webhook\GatewayController;
use App\Http\Controllers\Webhook\RedbillerController;
use App\Services\Payment\RedbillerService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function () {
   $name = getenv('APP_NAME');

   return [
      'url' => url()->current(),
      'service' => "$name Backend Service",
      'version' => '1.0.0',
      'environment' => env('APP_ENV')
   ];
});

Route::post('login', [LoginController::class, 'login']);
Route::post('check-token', [ResetsController::class, 'check']);
Route::post('password/reset-request', [ResetsController::class, 'passwordRequest']);
Route::put('password/reset', [ResetsController::class, 'passwordReset']);
Route::post('register/step/{step}', RegisterController::class);
Route::post('feedback', [AppController::class, 'sendFeedback']);

Route::withoutMiddleware('versionCheck')->group(function () {
   Route::group(['prefix' => 'front/crypto'], function () {
      Route::get('assets', [CryptoController::class, 'assets'])->name('front.crypto.asset');
      Route::get('rates', [CryptoController::class, 'rates']);
   });
});

Route::middleware('auth:sanctum')->group(function () {
   Route::post('login/pin', [LoginController::class, 'pinLogin']);
   Route::post('logout', [LoginController::class, 'logout']);
   Route::post('pin/reset-request', [ResetsController::class, 'pinRequest']);
   Route::post('pin/reset', [ResetsController::class, 'pinReset']);

   Route::post('biometric', [LoginController::class, 'setBiometric']);
   Route::put('biometric/remove', [LoginController::class, 'removeBiometric']);
   Route::post('login/biometric', [LoginController::class, 'checkBiometric']);
});

Route::middleware('auth:sanctum')->group(function () {
   Route::post('profile/edit', [ProfileController::class, 'edit']);
   Route::delete('user', [ProfileController::class, 'destroy']);
   Route::put('/change-password', [ProfileController::class, 'updatePassword']);
   Route::get('/tiers', [ProfileController::class, 'fetchTiers']);
   Route::get('/auth-user', [ProfileController::class, 'userDetails']);

   Route::prefix('fcm')->group(function () {
      Route::post('save-token', [ProfileController::class, 'saveDeviceToken']);
      Route::delete('delete-token', [ProfileController::class, 'deleteDeviceToken']);
   });

   Route::prefix('bank')->group(function () {
      Route::get('/', [BankController::class, 'banks']);
      Route::post('create', [BankController::class, 'create']);
      Route::post('{bank}/edit', [BankController::class, 'update']);
      Route::get('accounts', [BankController::class, 'accounts']);
      Route::delete('{bank}/delete', [BankController::class, 'destroy']);
      Route::post('verify', [BankController::class, 'verifyBankAccount']);
   });

   Route::prefix('pin')->group(function () {
      Route::post('create', [PinController::class, 'createPin']);
      Route::put('update', [PinController::class, 'updatePin']);
      Route::post('verify', [PinController::class, 'verifyPin']);
      Route::delete('delete', [PinController::class, 'destroyPin']);
   });

   Route::prefix('notifications')->group(function () {
      Route::get('/', [NotificationController::class, 'index']);
      Route::patch('{uid}/read', [NotificationController::class, 'read']);
      Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
   });

   Route::prefix('wallet')->group(function () {
      Route::get('/', [WalletController::class, 'walletDetails']);
      Route::apiBlock('withdraw')->post('withdraw', [WalletController::class, 'withdraw'])->middleware('active');
      Route::get('balance', [WalletController::class, 'balance']);
      Route::get('transactions/{type?}', [WalletController::class, 'transactions']);
   });

   Route::prefix('signup-bonus')->group(function () {
      Route::get('status', [SignUpBonusController::class, 'status']);
      Route::post('claim', [SignUpBonusController::class, 'claim'])->middleware('active');
   });

   Route::prefix('kyc')->group(function () {
      Route::post('/create', [KycController::class, 'createVerification']);
      // Route::post('verification', [KycController::class, 'createVerification']);
   });

   Route::prefix('phonebill')->group(function () {
      Route::get('/isp', [MobileBillController::class, 'providers']);
      Route::get('data-plans/{network}', [MobileBillController::class, 'dataPlans']);

      Route::apiBlock('data')->post('purchase-data', [MobileBillController::class, 'purchaseData'])->middleware('active');
      Route::apiBlock('airtime')->post('purchase-airtime', [MobileBillController::class, 'purchaseAirtime'])->middleware('active');
   });

   Route::prefix('cable')->group(function () {
      Route::apiBlock('cable')->post('purchase', [CableController::class, 'purchase'])->middleware('active');
      Route::get('providers', [CableController::class, 'providers']);
      Route::get('plans/{provider}', [CableController::class, 'plans']);
      Route::post('smartcard/verify', [CableController::class, 'verifySmartCard']);
   });

   Route::prefix('meter')->group(function () {
      Route::apiBlock('meter')->post('purchase', [MeterController::class, 'purchase'])->middleware('active');
      Route::get('providers', [MeterController::class, 'providers']);
      Route::post('device/verify', [MeterController::class, 'verifyMeter']);
   });

   Route::prefix('betting')->group(function () {
      Route::apiBlock('betting')->post('fund', [BettingController::class, 'fund'])->middleware('active');
      Route::get('providers', [BettingController::class, 'providers']);
      Route::post('account/verify', [BettingController::class, 'verify']);
   });

   Route::prefix('wifi')->group(function () {
      Route::apiBlock('wifi')->post('purchase', [WifiController::class, 'purchase'])->middleware('active');
      Route::get('providers', [WifiController::class, 'providers']);
      Route::get('plans/{product}', [WifiController::class, 'plans']);
      Route::post('device/verify', [WifiController::class, 'verifyDevice']);
   });

   Route::prefix('crypto')->group(function () {
      Route::get('wallets/{cryptoAsset:symbol}', [CryptoController::class, 'wallet']);
      Route::get('transactions', [CryptoController::class, 'transactions']);
      Route::post('wallets/create', [CryptoController::class, 'createWallet']);
   });

   Route::prefix('crypto/swap')->group(function () {
      Route::post('rates', [CryptoSwapController::class, 'swapRate']);
      Route::post('create', [CryptoSwapController::class, 'createSwap']);
      Route::get('currencies', [CryptoSwapController::class, 'currencies']);
      Route::get('pairs/{from}', [CryptoSwapController::class, 'swapPairs']);
      Route::get('{swapId}/details', [CryptoSwapController::class, 'swapDetails']);
   });

   Route::prefix('intercom')->group(function () {
      Route::post('generate-token', [IntercomController::class, 'generateToken']);
      Route::post('verify-token', [IntercomController::class, 'verifyToken']);
      Route::post('get-user', [IntercomController::class, 'getUserFromToken']);
      Route::post('generate-user-hash', [IntercomController::class, 'generateUserHash']);
   });

   Route::prefix('referral')->group(function () {
      Route::get('details', [ReferralController::class, 'getReferralDetails']);
      Route::post('claim', [ReferralController::class, 'claimReferralAmount']);
   });

   Route::group(['prefix' => 'trades'], function () {
        Route::get('/', [TradeController::class, 'index']);
        Route::get('/{trade}', [TradeController::class, 'view']);
        Route::post('/', [TradeController::class, 'create']);
    });

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', [GiftcardCategoryController::class, 'index']);
        Route::get('/{giftcardCategory}', [GiftcardCategoryController::class, 'view']);
        Route::get('/{giftcardCategory}/giftcards', [GiftcardController::class, 'index']);
        Route::get('/{giftcardCategory}/giftcards/{giftcard}', [GiftcardController::class, 'view']);
    });

    Route::get('/giftcards/high-rate', [GiftcardController::class, 'highRate']);
});

Route::group(['prefix' => 'crypto'], function () {
   Route::get('assets', [CryptoController::class, 'assets']);
   Route::get('assets/{symbol}', [CryptoController::class, 'asset']);
   Route::get('rates', [CryptoController::class, 'rates']);
});

Route::group(['prefix' => 'settings'], function () {
    Route::get('status', [AppController::class, 'systemStatus']);
});

Route::withoutMiddleware('versionCheck')->prefix('webhook')->group(function () {
   Route::any('redbiller', [GatewayController::class, 'redbiller']);
   Route::any('monnify',  [GatewayController::class, 'monnify']);

   // Route::any('cryptoapis', [CrpytoController::class, 'cryptoapis']);
   Route::any('vaultody', [CrpytoController::class, 'vaultody']);
   Route::any('xprocessing', [CrpytoController::class, 'xprocessing']);

   Route::any('data', [RedbillerController::class, 'data']);
   Route::any('airtime', [RedbillerController::class, 'airtime']);
   Route::any('betting', [RedbillerController::class, 'betting']);
   Route::any('meter', [RedbillerController::class, 'meter']);
   Route::any('cable', [RedbillerController::class, 'cable']);
   Route::any('wifi', [RedbillerController::class, 'wifi']);

   Route::any('kyc', [KycController::class, 'processVerification']);
});

Route::get('app-banners', [AppController::class, 'getAppBanners']);
Route::post('/bybit/spot/trade', [BybitController::class, 'createSpotTrade']);

/*
|--------------------------------------------------------------------------
| ADMIN Routes
|--------------------------------------------------------------------------
|
| Here are API routes for admin
|
*/
require __DIR__ . '/dashboard.php';
