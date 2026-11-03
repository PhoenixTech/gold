<?php

use App\Console\Commands\BackupDatabase;
use App\Models\AdminLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command("gold:free")
    ->everyFiveMinutes()
    ->withoutOverlapping(10, false);

Schedule::command("offline:expire")->everyFifteenMinutes();

Schedule::command(BackupDatabase::class, ["--days" => 5])->dailyAt("01:00");

Schedule::command("model:prune", ["--model" => [AdminLog::class]])->daily();
