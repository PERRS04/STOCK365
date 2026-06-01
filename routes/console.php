<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Capture operational health snapshot every hour
Schedule::command('oi:snapshot')->hourly();

// Compute workforce weekly scores every Monday at 06:00
Schedule::command('workforce:compute --period=weekly')->weeklyOn(1, '06:00');

// Compute daily scores every night at 23:30
Schedule::command('workforce:compute --period=daily')->dailyAt('23:30');
