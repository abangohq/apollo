<?php

use App\Http\Controllers\Admin\AirtimeTopupController;
use App\Http\Controllers\Admin\AppBannerController;
use App\Http\Controllers\Admin\BasicController;
use App\Http\Controllers\Admin\BettingTopupController;
use App\Http\Controllers\Admin\CableTopupController;
use App\Http\Controllers\Admin\CommonController;
use App\Http\Controllers\Admin\ConversionController;
use App\Http\Controllers\Admin\CryptoController;
use App\Http\Controllers\Admin\DataTopupController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MeterTopupController;
use App\Http\Controllers\Admin\PushNotificationController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\RejectionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\WifiTopupController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\GiftcardCategoryController;
use App\Http\Controllers\Admin\GiftcardController;
use App\Http\Controllers\Auth\ConsoleAuthController;
use App\Http\Controllers\Auth\ResetsController;
use App\Http\Controllers\User\KycController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| ADMIN Routes
|--------------------------------------------------------------------------
|
| Here are API routes for admin
|
*/

Route::prefix('console')->group(function () {
   Route::post('login', [ConsoleAuthController::class, 'login']);
   Route::post('password/reset-request', [ResetsController::class, 'passwordRequest']);
   Route::put('password/reset', [ResetsController::class, 'passwordReset']);
   Route::post('redbiller/bank-status', [BasicController::class, 'billerStatus']);
});

Route::prefix('console')->middleware(['auth:sanctum', 'console'])->group(function () {
   Route::get('overview', [BasicController::class, 'overview']);
   Route::get('heard-about-us', [BasicController::class, 'heardAboutUs']);
   Route::post('/export', [ExportController::class, 'export']);
   
   Route::get('redbiller/low-balance', [BasicController::class, 'redbillerLowBalance']);

   Route::prefix('users')->group(function () {
      Route::get('/', [UsersController::class, 'index']);
      Route::get('/{user}/basic', [UsersController::class, 'basic']);
      Route::get('/{user}/swaps', [UsersController::class, 'swapTransactions']);
      Route::get('/{user}/withdrawals', [UsersController::class, 'withdrawals']);
      Route::get('/{user}/deposits', [UsersController::class, 'deposits']);
      Route::get('/{user}/transactions', [UsersController::class, 'transactions']);
      Route::post('{user}/suspend', [UsersController::class, 'disable']);
      Route::post('{user}/unsuspend', [UsersController::class, 'enable']);
      Route::post('/{user}/flag', [UsersController::class, 'flag']);
      Route::post('/{user}/unflag', [UsersController::class, 'unflag']);
   });

   Route::prefix('staffs')->group(function () {
      Route::get('/', [StaffsController::class, 'staffs']);
      Route::get('roles', [StaffsController::class, 'roles']);
      Route::post('create', [StaffsController::class, 'create']);
      Route::delete('{user}', [StaffsController::class, 'delete']);
      Route::patch('{user}', [StaffsController::class, 'update']);
   });

   Route::group(['prefix' => 'banks'], function () {
      Route::get('/', [CommonController::class, 'banks']);
      Route::post('{bank}', [CommonController::class, 'updateBank']);
   });

   Route::prefix('settings')->group(function () {
      Route::get('/', [SettingsController::class, 'settings']);

      Route::post('app-version', [SettingsController::class, 'setVersion']);
      Route::get('app-version', [SettingsController::class, 'versions']);

      Route::post('withdrawal-limit', [SettingsController::class, 'withdrawLimit']);
      Route::post('withdrawal-mode', [SettingsController::class, 'withdrawalMode']);

      Route::post('update-sweep', [SettingsController::class, 'setSweepAddress']);

      Route::post('payment-gateway', [SettingsController::class, 'setPaymentGateway']);
      Route::get('payment', [SettingsController::class, 'paymentSettings']);

      Route::get('status', [SettingsController::class, 'systemStatus']);

      Route::post('platform/{systemStatus}', [SettingsController::class, 'setPlatformStatus']);
   });

   Route::prefix('notification')->group(function () {
      Route::post('bulletin', [PushNotificationController::class, 'bulletin']);
      Route::get('/', [PushNotificationController::class, 'index']);
      Route::post('newsletter', [PushNotificationController::class, 'sendNewsletter']);
   });

   Route::prefix('auth')->group(function () {
      Route::post('logout', [ConsoleAuthController::class, 'logout']);
      Route::post('{user}/update', [ConsoleAuthController::class, 'update']);
      Route::post('password/reset', [ConsoleAuthController::class, 'changePassword']);
   });

   Route::prefix('withdrawals')->group(function () {
      Route::get('/', [WithdrawalController::class, 'index']);
      Route::post('approve/{withdrawal}', [WithdrawalController::class, 'approve']);
      Route::post('decline/{withdrawal}', [WithdrawalController::class, 'decline']);
      Route::post('change-status/{withdrawal}', [WithdrawalController::class, 'changeStatus']);
      Route::get('employee/{userId}', [WithdrawalController::class, 'employeeWithdrawal']);
   });

   Route::apiResource('rejection-reasons', RejectionController::class)->parameter('rejection-reasons', 'rejectionReason');

   Route::prefix('reconciliations')->group(function () {
      Route::get('/', [WithdrawalController::class, 'reconciliations']);
      Route::post('create', [WithdrawalController::class, 'createReconciliation']);
      Route::get('reconciliable-transactions', [WithdrawalController::class, 'reconciliableTranx']);
   });

   Route::prefix('airtime')->group(function () {
      Route::get('topups', [AirtimeTopupController::class, 'topups']);
      Route::get('products', [AirtimeTopupController::class, 'products']);
      Route::post('products/{airtimeProduct}', [AirtimeTopUpController::class, 'update']);
   });

   Route::prefix('kycs')->group(function () {
      Route::get('/', [KycController::class, 'kycs']);
      Route::put('/{id}', [KycController::class, 'update']);
   });

   Route::prefix('betting')->group(function () {
      Route::get('topups', [BettingTopupController::class, 'topups']);
      Route::get('products', [BettingTopupController::class, 'products']);
      Route::post('products/{bettingProduct}', [BettingTopUpController::class, 'update']);
   });

   Route::prefix('cable')->group(function () {
      Route::get('topups', [CableTopupController::class, 'topups']);
      Route::get('products', [CableTopUpController::class, 'products']);
      Route::post('products/{cableProvider}', [CableTopUpController::class, 'update']);
   });

   Route::prefix('data')->group(function () {
      Route::get('topups', [DataTopupController::class, 'topups']);
      Route::get('products', [DataTopupController::class, 'products']);
      Route::post('products/{ispProvider}', [DataTopUpController::class, 'update']);
   });

   Route::prefix('disco')->group(function () {
      Route::get('topups', [MeterTopupController::class, 'topups']);
      Route::get('products', [MeterTopupController::class, 'products']);
      Route::post('products/{meterProduct}', [MeterTopUpController::class, 'update']);
   });

   Route::prefix('wifi')->group(function () {
      Route::get('topups', [WifiTopupController::class, 'topups']);
      Route::get('products', [WifiTopupController::class, 'products']);
      Route::post('products/{wifiProvider}', [WifiTopUpController::class, 'update']);
   });

   Route::prefix('conversion-rate')->group(function () {
      Route::get('/', [ConversionController::class, 'index']);
      Route::post('create', [ConversionController::class, 'store']);
      Route::put('/{cryptoRate}', [ConversionController::class, 'update']);
      Route::delete('/{cryptoRate}', [ConversionController::class, 'destroy']);
   });

   Route::prefix('referrals')->group(function () {
      Route::get('/', [ReferralController::class, 'referrals']);
      Route::get('codes', [ReferralController::class, 'codes']);
      Route::post('codes/create', [ReferralController::class, 'createCode']);
      Route::patch('codes/{referralCode}/update', [ReferralController::class, 'updateCode']);
      Route::delete('codes/{referralCode}/delete', [ReferralController::class, 'deleteCode']);
   });

    Route::prefix('signup-bonus')->group(function () {
      Route::get('/', [ReferralController::class, 'signUpBonuses']);
   });

   Route::prefix('crypto')->group(function () {
      Route::get('assets', [CryptoController::class, 'index']);
      Route::get('create', [CryptoController::class, 'create']);
      Route::get('swaps', [CryptoController::class, 'swaps']);
      Route::get('deposits', [CryptoController::class, 'transactions']);
      Route::get('deposits/{cryptoTransaction}', [CryptoController::class, 'transaction']);
      Route::post('{cryptoAsset}', [CryptoController::class, 'update']);
      Route::post('create', [CryptoController::class, 'create']);
      Route::get('wallet/balance', [CryptoController::class, 'walletBalance']);
      Route::post('wallet/withdraw', [CryptoController::class, 'withdraw']);
      Route::get('wallet/estimate', [CryptoController::class, 'estimate']);
      Route::get('/history/sweep', [CryptoController::class, 'sweepHistory']);
   });

   Route::apiResource('app-banners', AppBannerController::class);

   // Giftcards & Categories
   Route::apiResource('giftcard-categories', GiftcardCategoryController::class);
   Route::apiResource('giftcards', GiftcardController::class);

   Route::get('book-closures', [BasicController::class, 'bookClosures']);
});
