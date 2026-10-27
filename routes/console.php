<?php

use HughCube\Laravel\Knight\Console\Commands\LogsPruneCommand;
use HughCube\Laravel\Knight\Console\Commands\LogsRotateCommand;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(LogsRotateCommand::class)->dailyAt('00:00');
Schedule::command(LogsPruneCommand::class)->dailyAt('00:01');
