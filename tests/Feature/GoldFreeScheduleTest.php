<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class GoldFreeScheduleTest extends TestCase
{
    public function test_gold_free_command_runs_every_two_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $scheduled): bool => str_contains((string) $scheduled->command, 'gold:free'));

        $this->assertNotNull($event);
        $this->assertSame('*/2 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
