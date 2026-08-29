<?php

namespace LoserPool\Nfl;

/*
 * The 32 NFL teams, spelled exactly as ESPN's `team.displayName`.
 *
 * That exact-match property is what lets picks be compared to schedule data
 * without a name-mapping layer, so any edit here must keep it. This is the
 * single source of truth; the pick dropdown reads it too.
 */
final class Teams
{
    private const ALL = [
        'Arizona Cardinals',
        'Atlanta Falcons',
        'Baltimore Ravens',
        'Buffalo Bills',
        'Carolina Panthers',
        'Chicago Bears',
        'Cincinnati Bengals',
        'Cleveland Browns',
        'Dallas Cowboys',
        'Denver Broncos',
        'Detroit Lions',
        'Green Bay Packers',
        'Houston Texans',
        'Indianapolis Colts',
        'Jacksonville Jaguars',
        'Kansas City Chiefs',
        'Las Vegas Raiders',
        'Los Angeles Chargers',
        'Los Angeles Rams',
        'Miami Dolphins',
        'Minnesota Vikings',
        'New England Patriots',
        'New Orleans Saints',
        'New York Giants',
        'New York Jets',
        'Philadelphia Eagles',
        'Pittsburgh Steelers',
        'San Francisco 49ers',
        'Seattle Seahawks',
        'Tampa Bay Buccaneers',
        'Tennessee Titans',
        'Washington Commanders',
    ];

    /** @return string[] */
    public static function all(): array
    {
        return self::ALL;
    }

    public static function count(): int
    {
        return count(self::ALL);
    }
}
