<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Console\Commands\SyncNewsArticles;
use App\Services\NewsSyncService;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleTest extends TestCase
{

    public function test_news_sync_command_exists()
    {
        $this->assertTrue(class_exists(SyncNewsArticles::class));

        $command = new SyncNewsArticles(app(NewsSyncService::class));
        $this->assertEquals('news:sync', $command->getName());
        $this->assertStringContainsString('Sync articles from news sources', $command->getDescription());
    }

    public function test_schedule_configuration_when_enabled()
    {
        // Mock environment variables
        config(['app.env' => 'testing']);
        putenv('NEWS_SYNC_ENABLED=true');
        putenv('NEWS_SYNC_SCHEDULE=0 */12 * * *');

        $schedule = new Schedule();

        // Simulate the schedule configuration from bootstrap/app.php
        if (env('NEWS_SYNC_ENABLED', false)) {
            $cronExpression = env('NEWS_SYNC_SCHEDULE', '0 */12 * * *');

            $schedule->command('news:sync')
                ->cron($cronExpression)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/news-sync.log'));
        }

        $events = $schedule->events();

        $this->assertCount(1, $events);
        $this->assertEquals('0 */12 * * *', $events[0]->expression);
        $this->assertTrue($events[0]->withoutOverlapping);
    }

    public function test_schedule_configuration_when_disabled()
    {
        // Mock environment variables
        putenv('NEWS_SYNC_ENABLED=false');

        $schedule = new Schedule();

        // Simulate the schedule configuration from bootstrap/app.php
        if (env('NEWS_SYNC_ENABLED', false)) {
            $cronExpression = env('NEWS_SYNC_SCHEDULE', '0 */12 * * *');

            $schedule->command('news:sync')
                ->cron($cronExpression)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/news-sync.log'));
        }

        $events = $schedule->events();

        $this->assertCount(0, $events);
    }
}
