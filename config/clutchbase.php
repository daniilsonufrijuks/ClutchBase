<?php

return [

    'name' => 'ClutchBase',

    // JSON parsed from the CS2 game files Steam distributes. This is not a Steam Market feed.
    'cs2_catalog_url' => env('CS2_CATALOG_URL', 'https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en'),

    'steam_web_api_key' => env('STEAM_WEB_API_KEY'),

    'steam_app_id' => 730,

    'ssr' => (bool) env('INERTIA_SSR_ENABLED', false),

    'price_stale_days' => 7,

    'history_days_free' => 30,

    'free_wishlists' => 1,

    'premium_wishlists' => 10,

    'free_alerts' => 0,

    'premium_alerts' => 50,

    'disclaimers' => [
        'demo' => 'Demo records and demo prices are sample data for local development. They are not verified game attributes or live market quotes.',
        'strategy' => 'Strategy templates are editable examples. Timings and utility notes are not verified lineups and are not guaranteed to work in a match.',
    ],

];
