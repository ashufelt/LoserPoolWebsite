#!/usr/bin/env php
<?php

/*
 * Prints what the pool rules currently believe, with no database and no web
 * server involved. Useful for eyeballing a season before it starts, and for
 * checking behaviour on a host where you cannot easily run the site.
 *
 *     php bin/pool-status.php          # current week
 *     php bin/pool-status.php all      # every week of the season
 */

require_once __DIR__ . '/../src/week_manager.php';

use LoserPool\Pool\SeasonConfig;

$mode = $argv[1] ?? 'current';

printf("Season %d  (timezone %s)\n", SeasonConfig::YEAR, SeasonConfig::TIMEZONE);
printf("Blocked kickoff days: %s\n", implode(', ', SeasonConfig::BLOCKED_KICKOFF_DAYS));
printf("Current week: %d\n\n", get_current_week());

$weeks = $mode === 'all' ? range(1, SeasonConfig::REGULAR_SEASON_WEEKS) : [get_current_week()];

foreach ($weeks as $week) {
    $ineligible = get_INELIGIBLE_teams($week);
    printf("Week %2d - %d unavailable\n", $week, count($ineligible));
    foreach ($ineligible as $team) {
        printf("    %s\n", $team);
    }
}
