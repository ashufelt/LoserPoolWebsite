<?php

namespace LoserPool\Tests;

use DateTimeImmutable;
use LoserPool\Nfl\Schedule;
use LoserPool\Nfl\Teams;
use LoserPool\Pool\SeasonConfig;

/*
 * Builds synthetic weeks so a whole season can be played out in a test.
 *
 * The fixtures elsewhere are real ESPN responses, which is right for proving
 * the parser handles the real payload shape. They are the wrong tool for
 * simulating a season: their results are whatever actually happened, so a test
 * cannot say "this player's team loses in week 3" and watch the consequences.
 *
 * Teams are paired in a fixed order, so a given week is reproducible, and the
 * caller names which teams lose.
 */
final class SeasonBuilder
{
    /* Sunday of a given week, in the pool's timezone. */
    public static function sunday(int $week): DateTimeImmutable
    {
        return SeasonConfig::weekOneStart()->modify('+' . (5 + ($week - 1) * 7) . ' days');
    }

    public static function tuesday(int $week): DateTimeImmutable
    {
        return SeasonConfig::weekOneStart()->modify('+' . (($week - 1) * 7) . ' days');
    }

    /**
     * @param string[] $losers teams that lose this week
     * @param string[] $thursdayTeams teams playing Thursday, so unpickable
     */
    public static function week(
        int $week,
        array $losers = [],
        bool $completed = true,
        array $thursdayTeams = []
    ): Schedule {
        $teams = Teams::all();
        $sunday = self::sunday($week);
        $thursday = $sunday->modify('-3 days');

        $events = [];
        for ($i = 0; $i < count($teams); $i += 2) {
            $pair = [$teams[$i], $teams[$i + 1]];

            /* Whoever is named loses; otherwise the first team wins. */
            if (in_array($pair[0], $losers, true)) {
                $winner = $pair[1];
            } elseif (in_array($pair[1], $losers, true)) {
                $winner = $pair[0];
            } else {
                $winner = $pair[0];
            }

            $playsThursday = in_array($pair[0], $thursdayTeams, true)
                || in_array($pair[1], $thursdayTeams, true);
            $kickoff = $playsThursday ? $thursday : $sunday;

            $events[] = [
                'date' => $kickoff->setTime(12, 0)->format('c'),
                'competitions' => [[
                    'status' => ['type' => ['completed' => $completed]],
                    'competitors' => [
                        ['team' => ['displayName' => $pair[0]], 'winner' => $completed && $winner === $pair[0]],
                        ['team' => ['displayName' => $pair[1]], 'winner' => $completed && $winner === $pair[1]],
                    ],
                ]],
            ];
        }

        return Schedule::fromEspnPayload(['events' => $events], SeasonConfig::timezone());
    }

    /* A tie: finished, with no winner on either side. */
    public static function weekWithTie(int $week, string $teamA, string $teamB): Schedule
    {
        $sunday = self::sunday($week);

        return Schedule::fromEspnPayload(['events' => [[
            'date' => $sunday->setTime(12, 0)->format('c'),
            'competitions' => [[
                'status' => ['type' => ['completed' => true]],
                'competitors' => [
                    ['team' => ['displayName' => $teamA], 'winner' => false],
                    ['team' => ['displayName' => $teamB], 'winner' => false],
                ],
            ]],
        ]]], SeasonConfig::timezone());
    }
}
