<?php

namespace LoserPool\Pool;

use DateTimeImmutable;

/*
 * Works out which week the pool is in.
 *
 * The previous implementation derived this from the day of the year using
 * constants tuned to one specific season, plus a hardcoded `if year == 2026
 * return 18` that pinned the site to a finished season. Both are replaced by
 * asking ESPN, with calendar arithmetic as an offline fallback.
 *
 * Pure: every input is a parameter, so each branch is directly testable.
 */
final class WeekResolver
{
    public const PRESEASON = 1;
    public const REGULAR_SEASON = 2;

    /**
     * @param array|null $current Result of ScheduleSource::currentSeasonWeek(),
     *                            or null when ESPN could not be reached.
     */
    public static function resolve(
        ?array $current,
        int $seasonYear,
        DateTimeImmutable $now,
        DateTimeImmutable $weekOneStart,
        int $maxWeeks
    ): int {
        if ($current !== null && (int) ($current['year'] ?? 0) === $seasonYear) {
            $type = (int) ($current['seasonType'] ?? 0);

            if ($type === self::PRESEASON) {
                return 1; /* season hasn't started; week 1 is what people pick */
            }
            if ($type === self::REGULAR_SEASON) {
                return self::clamp((int) ($current['week'] ?? 1), $maxWeeks);
            }
            return $maxWeeks; /* playoffs or later: the pool is over */
        }

        /*
         * No usable live answer -- either the fetch failed, or ESPN has already
         * rolled on to another season's preseason while we are still configured
         * for this one. Fall back to the calendar.
         */
        return self::fromCalendar($now, $weekOneStart, $maxWeeks);
    }

    /*
     * Pool weeks run Tuesday to Monday, so $weekOneStart is the Tuesday before
     * the season opener rather than the opener itself.
     */
    private static function fromCalendar(DateTimeImmutable $now, DateTimeImmutable $weekOneStart, int $maxWeeks): int
    {
        if ($now < $weekOneStart) {
            return 1;
        }
        $elapsedDays = (int) $weekOneStart->diff($now)->days;
        return self::clamp(intdiv($elapsedDays, 7) + 1, $maxWeeks);
    }

    private static function clamp(int $week, int $maxWeeks): int
    {
        if ($week < 1) {
            return 1;
        }
        return $week > $maxWeeks ? $maxWeeks : $week;
    }
}
