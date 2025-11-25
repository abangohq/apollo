<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'from' => env('MAIL_FROM_ADDRESS'),
        'trade_address' => env('MAIL_TRADE_ADDRESS'),
        'scheme' => 'https',
    ],

    'plunk' => [
        'secretKey' => env('PLUNK_SECRET_KEY'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'fcm' => [
        'key' => env('FCM_SECRET_KEY')
    ],

    'xprocess' => [
        'production' => env('XPROCESSING_PRODUCTION'),
        'secretKey' => env('XPROCESSING_KEY'),
        'password' => env('XPROCESSING_PASSWORD'),
        'url' => env('XPROCESSING_URL'),
    ],

    'dojah' => [
        'apiKey' => env('DOJAH_API_KEY'),
        'url' => env('DOJAH_BASE_URL'),
        'selfieApp' => env('DOJAH_SELFIE_APP')
    ],

    'monnify' => [
        'production' => env('MONNIFY_PRODUCTION'),
        'url' => env('MONNIFY_BASE_URL'),
        'secret' => env('MONNIFY_SECRET_KEY'),
        'key' => env('MONNIFY_API_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
        'account_number' => env('MONNIFY_ACCOUNT'),
        'basic' => [env('MONNIFY_API_KEY'), env('MONNIFY_SECRET_KEY')],
        'authPath' => env('MONNIFY_AUTH_ENDPOINT')
    ],

    // 'cryptoapis' => [
    //    'key' => env('CRYPTOAPIS_API_KEY'),
    //    'wallet_id' => env('CRYPTOAPIS_WALLET_ID'),
    //    'url' => env('CRYPTOAPIS_URL'),
    //    'callback' => env('CRYPTOAPIS_CALLBACK'),
    //    'callback_secret' => env('CRYPTOAPIS_CALLBACK_SECRET')
    // ],

    'vaultody' => [
        'key' => env('VAULTODY_API_KEY'),
        'wallet_id' => env('VAULTODY_VAULT_ID'),
        'vault_id' => env('VAULTODY_GENERAL_VAULT_ID'),
        'url' => env('VAULTODY_URL'),
        'callback' => env('VAULTODY_CALLBACK'),
        'passphrase' => env('VAULTODY_PASSPHRASE'),
        'secret' => env('VAULTODY_SECRET')
    ],

    'redbiller' => [
        'secretKey' => getenv('REDBILLER_KEY'),
        'paymentUrl' => getenv('REDBILLER_URL'),
        'production' =>  env('REDBILLER_PRODUCTION'),
        'transferCallback' => env('TRANSFER_CALLBACK'),
        'airtime_callback' => env('REDBILLER_AIRTIME_CALLBACK'),
        'betting_callback' => env('REDBILLER_BETTING_CALLBACK'),
        'cable_callback' => env('REDBILLER_CABLE_CALLBACK'),
        'data_callback' => env('REDBILLER_DATA_CALLBACK'),
        'meter_callback' =>  env('REDBILLER_METER_CALLBACK'),
        'wifi_callback' =>  env('REDBILLER_WIFI_CALLBACK'),
        'low_balance_threshold' => env('REDBILLER_LOW_BALANCE_THRESHOLD')
    ],

    'changelly' => [
        'url' => env('CHANGELLY_URL'),
        'key' => env('CHANGELLY_API_KEY'),
        'privateKey' => env('CHANGELLY_PRIVATE_KEY'),
        'id' => env('CHANGELLY_CID', 'production')
    ],

    'reloadly' => [
        'production' => env('RELOADLY_PRODUCTION'),
        'secretKey' => getenv('RELOADLY_SECRET'),
        'clientId' => getenv('RELOADLY_CLIENT_ID')
    ],

    'fincra' => [
        'production' => env('FINCRA_PRODUCTION'),
        'base_url' => env('FINCRA_BASE_URL'),
        'apiKey' => env('FINCRA_API_KEY'),
        'webhookKey' => env('FINCRA_WEBHOOK_KEY'),
        'channel' => env('VIRTUAL_ACCOUNT_CHANNEL')
    ],

    'gecko' => [
        'url' => env('GECKO_URL')
    ],

    'bybit' => [
        'api_key' => env('BYBIT_API_KEY'),
        'secret_key' => env('BYBIT_SECRET_KEY'),
        'base_url' => env('BYBIT_API_URL', 'https://api.bybit.com'),
    ],

    'intercom' => [
        'app_id' => env('INTERCOM_APP_ID'),
        'web_secret_key' => env('INTERCOM_WEB_SECRET_KEY'),
        'android_secret_key' => env('INTERCOM_ANDROID_SECRET_KEY'),
        'ios_secret_key' => env('INTERCOM_IOS_SECRET_KEY')
    ],
];
