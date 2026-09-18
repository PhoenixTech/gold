<?php

use App\Models\AdminLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('gold')->everyFiveMinutes();

Schedule::command('gold:free')
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::command('offline:expire')->everyFifteenMinutes();

Schedule::command('model:prune', ['--model' => [AdminLog::class]])->daily();
