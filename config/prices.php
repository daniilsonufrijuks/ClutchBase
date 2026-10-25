<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Price provider
    |--------------------------------------------------------------------------
    |
    | "demo" records nothing from the network. Seeded demo observations stay
    | labeled is_demo. "http" calls PRICE_API_URL with PRICE_API_TOKEN.
    | Credentials are read only on the server.
    |
    */

    'provider' => env('PRICE_PROVIDER', 'demo'),

    'http' => [
        'url' => env('PRICE_API_URL'),
        'token' => env('PRICE_API_TOKEN'),
        'timeout' => (int) env('PRICE_API_TIMEOUT', 10),
    ],

    'subscription' => [
        'provider' => env('SUBSCRIPTION_PROVIDER'),
        'stripe_key' => env('STRIPE_KEY'),
        'stripe_secret' => env('STRIPE_SECRET'),
        'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
