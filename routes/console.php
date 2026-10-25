<?php

use App\Jobs\SyncMarketPrices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('prices:sync', function () {
    SyncMarketPrices::dispatchSync();
    $this->info('Price sync finished.');
})->purpose('Fetch prices from the configured provider');

Schedule::job(new SyncMarketPrices)->hourly();
