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

    /*
     * ESPN's team abbreviations, used to find each team's crest in
     * htdocs/img/teams/. Kept as a map rather than derived at runtime so a
     * logo never depends on a network call succeeding.
     */
    private const ABBREVIATIONS = [
        'Arizona Cardinals' => 'ari',
        'Atlanta Falcons' => 'atl',
        'Baltimore Ravens' => 'bal',
        'Buffalo Bills' => 'buf',
        'Carolina Panthers' => 'car',
        'Chicago Bears' => 'chi',
        'Cincinnati Bengals' => 'cin',
        'Cleveland Browns' => 'cle',
        'Dallas Cowboys' => 'dal',
        'Denver Broncos' => 'den',
        'Detroit Lions' => 'det',
        'Green Bay Packers' => 'gb',
        'Houston Texans' => 'hou',
        'Indianapolis Colts' => 'ind',
        'Jacksonville Jaguars' => 'jax',
        'Kansas City Chiefs' => 'kc',
        'Las Vegas Raiders' => 'lv',
        'Los Angeles Chargers' => 'lac',
        'Los Angeles Rams' => 'lar',
        'Miami Dolphins' => 'mia',
        'Minnesota Vikings' => 'min',
        'New England Patriots' => 'ne',
        'New Orleans Saints' => 'no',
        'New York Giants' => 'nyg',
        'New York Jets' => 'nyj',
        'Philadelphia Eagles' => 'phi',
        'Pittsburgh Steelers' => 'pit',
        'San Francisco 49ers' => 'sf',
        'Seattle Seahawks' => 'sea',
        'Tampa Bay Buccaneers' => 'tb',
        'Tennessee Titans' => 'ten',
        'Washington Commanders' => 'wsh',
    ];

    /* Lowercase abbreviation, or null for a name we do not recognise. */
    public static function abbreviation(string $displayName): ?string
    {
        return self::ABBREVIATIONS[$displayName] ?? null;
    }

    /*
     * Path to a team's crest, or null. Callers must handle null: a pick
     * recorded under a renamed or relocated franchise still has to render.
     */
    public static function logoPath(string $displayName): ?string
    {
        $abbreviation = self::abbreviation($displayName);
        return $abbreviation === null ? null : '/img/teams/' . $abbreviation . '.png';
    }

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
