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


    /*
     * Abbreviation and the two club colours, as ESPN reports them.
     *
     * Held as a map rather than fetched, so neither a crest nor a colour ever
     * depends on a network call succeeding. Colours are used as accents in the
     * team picker; they are never used to carry meaning on their own, since
     * several clubs share very similar primaries.
     */
    private const TEAM_DATA = [
        'Arizona Cardinals' => ['ari', '#a40227', '#ffffff'],
        'Atlanta Falcons' => ['atl', '#a71930', '#000000'],
        'Baltimore Ravens' => ['bal', '#29126f', '#000000'],
        'Buffalo Bills' => ['buf', '#00338d', '#d50a0a'],
        'Carolina Panthers' => ['car', '#0085ca', '#000000'],
        'Chicago Bears' => ['chi', '#0b1c3a', '#e64100'],
        'Cincinnati Bengals' => ['cin', '#fb4f14', '#000000'],
        'Cleveland Browns' => ['cle', '#472a08', '#ff3c00'],
        'Dallas Cowboys' => ['dal', '#002a5c', '#b0b7bc'],
        'Denver Broncos' => ['den', '#0a2343', '#fc4c02'],
        'Detroit Lions' => ['det', '#0076b6', '#bbbbbb'],
        'Green Bay Packers' => ['gb', '#204e32', '#ffb612'],
        'Houston Texans' => ['hou', '#021018', '#eb0028'],
        'Indianapolis Colts' => ['ind', '#003b75', '#ffffff'],
        'Jacksonville Jaguars' => ['jax', '#007487', '#d7a22a'],
        'Kansas City Chiefs' => ['kc', '#e31837', '#ffb612'],
        'Las Vegas Raiders' => ['lv', '#000000', '#a5acaf'],
        'Los Angeles Chargers' => ['lac', '#0080c6', '#ffc20e'],
        'Los Angeles Rams' => ['lar', '#003594', '#ffd100'],
        'Miami Dolphins' => ['mia', '#008e97', '#fc4c02'],
        'Minnesota Vikings' => ['min', '#4f2683', '#ffc62f'],
        'New England Patriots' => ['ne', '#002a5c', '#c60c30'],
        'New Orleans Saints' => ['no', '#d3bc8d', '#000000'],
        'New York Giants' => ['nyg', '#003c7f', '#c9243f'],
        'New York Jets' => ['nyj', '#115740', '#ffffff'],
        'Philadelphia Eagles' => ['phi', '#06424d', '#000000'],
        'Pittsburgh Steelers' => ['pit', '#000000', '#ffb612'],
        'San Francisco 49ers' => ['sf', '#aa0000', '#b3995d'],
        'Seattle Seahawks' => ['sea', '#002a5c', '#69be28'],
        'Tampa Bay Buccaneers' => ['tb', '#bd1c36', '#3e3a35'],
        'Tennessee Titans' => ['ten', '#4495d2', '#001532'],
        'Washington Commanders' => ['wsh', '#5a1414', '#ffb612'],
    ];

    /* Lowercase abbreviation, or null for a name we do not recognise. */
    public static function abbreviation(string $displayName): ?string
    {
        return self::TEAM_DATA[$displayName][0] ?? null;
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

    /* Primary club colour as a hex string, or null if unrecognised. */
    public static function color(string $displayName): ?string
    {
        return self::TEAM_DATA[$displayName][1] ?? null;
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
