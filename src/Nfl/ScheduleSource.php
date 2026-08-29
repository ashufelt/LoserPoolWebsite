<?php

namespace LoserPool\Nfl;

/*
 * Where schedules come from. Implemented by EspnClient in production and by
 * fakes in tests, so the rules can be exercised without a network.
 */
interface ScheduleSource
{
    public function weekSchedule(int $season, int $week): Schedule;

    /*
     * The season/week the NFL is currently in, or null if unknown.
     * Shape: ['year' => int, 'seasonType' => int, 'week' => int]
     * seasonType: 1 = preseason, 2 = regular season, 3+ = postseason.
     */
    public function currentSeasonWeek(): ?array;
}
