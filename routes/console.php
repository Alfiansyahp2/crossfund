<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('scraper:run')->dailyAt('00:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
