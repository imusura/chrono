<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:sync-public-holidays')->yearlyOn(1, 1, '03:00');
Schedule::command('app:run-annual-leave-reset')->dailyAt('02:00');
Schedule::command('app:run-carryover-expiry')->dailyAt('02:30');
