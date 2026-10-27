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

Schedule::command(LogsRotateCommand::class, [
    '--pattern' => ['php-error.log', 'php-opcache-error.log'],
])->dailyAt('00:00');

Schedule::command(LogsPruneCommand::class, [
    '--days' => 30,
    '--pattern' => [
        '/^apache-.*-\d{4}-\d{2}-\d{2}\.log$/',
        '/^laravel-.*-\d{4}-\d{2}-\d{2}\.log$/',
        '/^php-.*-\d{4}-\d{2}-\d{2}\.log$/',
    ],
])->dailyAt('00:01');
