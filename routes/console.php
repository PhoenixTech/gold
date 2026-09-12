<?php

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

Schedule::command('model:prune', ['--model' => [\App\Models\AdminLog::class]])->daily();

Artisan::command('adminlogs:clean', function () {
    $count = \App\Models\AdminLog::where('created_at', '<=', now()->subMonth())->delete();
    $this->info("Cleaned up {$count} admin logs older than 1 month.");
})->purpose('Clean up admin logs older than 1 month')->daily();
