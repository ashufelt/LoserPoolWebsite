<?php

namespace LoserPool\Pool;

use DateTimeImmutable;
use DateTimeZone;

/*
 * Everything that changes from one season to the next, in one place.
 *
 * Rolling the pool over to a new year should be an edit to this file and
 * nothing else.
 */
final class SeasonConfig
{
    /* The season to run. Bump this in the offseason. */
    public const YEAR = 2026;

    /* Suffix for that season's database tables, e.g. Users_26 / Picks_26. */
    public const TABLE_SUFFIX = '26';

    /* The pool's home timezone; every deadline is expressed in it. */
    public const TIMEZONE = 'America/Chicago';

    /*
     * Teams playing on these days cannot be picked, because their games start
     * before the pick deadline. Saturday is deliberately absent: late-season
     * Saturday slates are pickable, and in 2026 week 18 is Saturday-only.
     */
    public const BLOCKED_KICKOFF_DAYS = ['Wed', 'Thu', 'Fri'];

    public const REGULAR_SEASON_WEEKS = 18;

    /*
     * Tuesday before the season opener -- pool weeks run Tuesday to Monday.
     * Only used when ESPN cannot be reached.
     */
    public const WEEK_ONE_START = "2026-09-08 00:00:00";

    /* How long an unfinished week's data stays fresh. Finished weeks never expire. */
    public const LIVE_CACHE_TTL_SECONDS = 600;

    /* Give up on ESPN quickly; a slow page is worse than a stale one. */
    public const HTTP_TIMEOUT_SECONDS = 5;

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }

    public static function weekOneStart(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::WEEK_ONE_START, self::timezone());
    }
}
