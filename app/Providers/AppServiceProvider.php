<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use phpseclib3\Crypt\RSA;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerHttpMacros();

        Model::preventSilentlyDiscardingAttributes(env('APP_ENV') == 'local');

        Route::macro('apiBlock', function ($key) {
            return Route::middleware("block:$key");
        });

        Relation::morphMap([
            'withdraw' => 'App\Models\Withdrawal',
            'data' => 'App\Models\DataTopUp',
            'airtime' => 'App\Models\AirtimeTopUp',
            'meter' => 'App\Models\MeterTopUp',
            'betting' => 'App\Models\BettingTopUp',
            'cable' => 'App\Models\CableTopUp',
            'wifi' =>  'App\Models\WifiTopUp',
            'crypto' => 'App\Models\CryptoTransaction',
            'reconcile' => 'App\Models\Reconciliation',
            'swap' => 'App\Models\SwapTransaction'
        ]);
    }

    public function registerHttpMacros(): void
    {
        $timeout = 30;

        Http::macro('fincra', function () use ($timeout) {
            return Http::acceptJson()->timeout($timeout)->withHeaders([
                'api-key' => config('services.fincra.apiKey')
            ])->baseUrl(env('FINCRA_BASE_URL'));
        });

        Http::macro('dojah', function () use ($timeout) {
            return Http::acceptJson()->timeout($timeout)->withHeaders([
                ['Authorization' => config('services.dojah.apiKey')]
            ])->baseUrl(config('services.dojah.url'));
        });

        Http::macro('redbiller', function () use ($timeout) {
            return Http::acceptJson()->timeout($timeout)->withHeaders([
                'Private-Key' => config('services.redbiller.secretKey')
            ])->baseUrl(config('services.redbiller.paymentUrl'));
        });

        Http::macro('monnify', function () use ($timeout) {
            [$key, $secret] = config('services.monnify.basic');
            $authUrl = config('services.monnify.authPath');

            $auth = Http::withBasicAuth($key, $secret)
                ->baseUrl(config('services.monnify.url'))
                ->post($authUrl)->throw()->object();

            return Http::withToken($auth->responseBody->accessToken)
                ->asJson()->acceptJson()->timeout($timeout)
                ->baseUrl(config('services.monnify.url'));
        });

        Http::macro('changelly', function () use ($timeout) {
            return Http::acceptJson()->withHeaders([
                'X-Api-Key' => config('services.changelly.key')
            ])->baseUrl(config('services.changelly.url'));
        });

        Http::macro('xprocess', function () use ($timeout) {
            return Http::acceptJson()->withHeaders([
                "APIKEY" => config('services.xprocess.secretKey')
            ])->baseUrl(config('services.xprocess.url'));
        });

        Http::macro('gecko', function () use ($timeout) {
            return Http::acceptJson()->baseUrl(config('services.gecko.url'));
        });
    }
}
