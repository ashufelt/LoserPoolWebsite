<?php

namespace LoserPool\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use LoserPool\Pool\WeekResolver;
use PHPUnit\Framework\TestCase;

/*
 * Which week is it?
 *
 * The old implementation pinned the site to week 18 for the whole of 2026 via
 * a hardcoded `if ($year == 2026) return 18;`, on top of day-of-year constants
 * tuned to the 2025 calendar. These tests exist so neither can come back.
 */
final class WeekResolverTest extends TestCase
{
    private const SEASON = 2026;
    private const MAX_WEEKS = 18;

    private function chicago(string $when): DateTimeImmutable
    {
        return new DateTimeImmutable($when, new DateTimeZone('America/Chicago'));
    }

    private function weekOneStart(): DateTimeImmutable
    {
        return $this->chicago('2026-09-08 00:00:00');
    }

    private function resolve(?array $current, string $now): int
    {
        return WeekResolver::resolve(
            $current,
            self::SEASON,
            $this->chicago($now),
            $this->weekOneStart(),
            self::MAX_WEEKS
        );
    }

    public function testUsesTheLiveWeekDuringTheRegularSeason(): void
    {
        $current = ['year' => 2026, 'seasonType' => 2, 'week' => 7];

        $this->assertSame(7, $this->resolve($current, '2026-10-20 09:00:00'));
    }

    /* Before kickoff the pool is taking week 1 picks, not week 0. */
    public function testPreseasonResolvesToWeekOne(): void
    {
        $current = ['year' => 2026, 'seasonType' => 1, 'week' => 4];

        $this->assertSame(1, $this->resolve($current, '2026-08-28 09:00:00'));
    }

    public function testPostseasonPinsToTheFinalWeek(): void
    {
        $current = ['year' => 2026, 'seasonType' => 3, 'week' => 1];

        $this->assertSame(18, $this->resolve($current, '2027-01-15 09:00:00'));
    }

    public function testOutOfRangeLiveWeekIsClamped(): void
    {
        $this->assertSame(18, $this->resolve(['year' => 2026, 'seasonType' => 2, 'week' => 23], '2027-01-05 09:00:00'));
        $this->assertSame(1, $this->resolve(['year' => 2026, 'seasonType' => 2, 'week' => 0], '2026-09-10 09:00:00'));
    }

    /*
     * ESPN rolls on to the next season's preseason while the pool is still
     * configured for this one. That must not be read as "we are in week 4".
     */
    public function testADifferentSeasonYearFallsBackToTheCalendar(): void
    {
        $current = ['year' => 2027, 'seasonType' => 1, 'week' => 4];

        $this->assertSame(5, $this->resolve($current, '2026-10-08 09:00:00'));
    }

    public function testFallsBackToTheCalendarWhenEspnIsUnreachable(): void
    {
        $this->assertSame(1, $this->resolve(null, '2026-09-09 12:00:00'));
        $this->assertSame(2, $this->resolve(null, '2026-09-15 12:00:00'));
        $this->assertSame(3, $this->resolve(null, '2026-09-22 12:00:00'));
    }

    public function testCalendarFallbackReturnsWeekOneBeforeTheSeasonStarts(): void
    {
        $this->assertSame(1, $this->resolve(null, '2026-07-01 12:00:00'));
        $this->assertSame(1, $this->resolve(null, '2026-09-07 23:59:00'));
    }

    public function testCalendarFallbackNeverExceedsTheFinalWeek(): void
    {
        $this->assertSame(18, $this->resolve(null, '2027-06-01 12:00:00'));
    }

    /*
     * Regression: the site reported week 18 for every date in 2026 because the
     * year alone decided the answer.
     */
    public function testTheYearAloneDoesNotEndTheSeason(): void
    {
        $inSeason = $this->resolve(['year' => 2026, 'seasonType' => 2, 'week' => 3], '2026-09-24 09:00:00');

        $this->assertSame(3, $inSeason);
        $this->assertNotSame(18, $inSeason);
    }
}
