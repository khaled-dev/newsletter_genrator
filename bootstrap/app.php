<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        if (env('NEWS_SYNC_ENABLED', false)) {
            $cronExpression = env('NEWS_SYNC_SCHEDULE', '0 */12 * * *');

            $schedule->command('news:sync')
                ->cron($cronExpression)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/news-sync.log'));
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
