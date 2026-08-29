<?php

namespace LoserPool\Tests;

use LoserPool\Nfl\Schedule;
use LoserPool\Nfl\ScheduleSource;

/*
 * A schedule source backed by fixtures, so tests never touch the network.
 */
final class FakeScheduleSource implements ScheduleSource
{
    private array $schedulesByWeek;
    private ?array $current;

    /** @param array<int,Schedule> $schedulesByWeek */
    public function __construct(array $schedulesByWeek = [], ?array $current = null)
    {
        $this->schedulesByWeek = $schedulesByWeek;
        $this->current = $current;
    }

    public function weekSchedule(int $season, int $week): Schedule
    {
        return $this->schedulesByWeek[$week] ?? Schedule::emptySchedule();
    }

    public function currentSeasonWeek(): ?array
    {
        return $this->current;
    }
}
