<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Inspiring;
use App\Jobs\UnlockInvestmentsJob;

Schedule::command('scraper:run')->dailyAt('00:00');
Schedule::job(new UnlockInvestmentsJob)->dailyAt('01:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
