<?php

namespace LoserPool\Pool;

use LoserPool\Nfl\Schedule;

/*
 * The pool's rules, as pure functions over a Schedule.
 *
 * Nothing here touches the network, the database, the clock, or the session.
 * Given the same Schedule it always returns the same answer, which is what
 * makes the rules testable against real recorded ESPN payloads.
 */
final class Rules
{
    /* check() outcomes, from the pool's perspective: you PICK a team to lose. */
    public const PICK_CORRECT = 1;    // the team lost -- good pick
    public const PICK_INCORRECT = -1; // the team won -- eliminated
    public const PICK_UNDECIDED = 0;  // not finished, tied, or unknown

    /*
     * Teams a player may not pick this week:
     *   - teams on a bye (no game at all), and
     *   - teams whose game kicks off before the pick deadline.
     *
     * The second rule is expressed as a list of weekday abbreviations rather
     * than "before Sunday". That distinction is load-bearing: the 2026 opener
     * is a Wednesday game, and week 18 is played entirely on Saturday -- a
     * "block anything before Sunday" rule would have made week 18 unpickable
     * for all 32 teams.
     *
     * A schedule we could not load blocks nothing. Silently blocking every
     * team would look identical to a bye week and would quietly break picking;
     * an unrestricted dropdown is the safer failure.
     *
     * @param string[] $allTeams
     * @param string[] $blockedKickoffDays e.g. ['Wed', 'Thu', 'Fri']
     * @return string[]
     */
    public static function ineligibleTeams(Schedule $schedule, array $allTeams, array $blockedKickoffDays): array
    {
        if ($schedule->isEmpty()) {
            return [];
        }

        $playing = $schedule->teamsPlaying();
        $ineligible = array_values(array_diff($allTeams, $playing));

        foreach ($schedule->games() as $game) {
            if (in_array($game->kickoffDay(), $blockedKickoffDays, true)) {
                foreach ($game->teams() as $team) {
                    $ineligible[] = $team;
                }
            }
        }

        $ineligible = array_values(array_unique($ineligible));
        sort($ineligible);
        return $ineligible;
    }

    /*
     * Did this pick come in? Returns one of the PICK_* constants.
     *
     * A tie eliminates the player. You are picking a team to lose, and a team
     * that ties did not lose -- so the pick failed. This is not the same as
     * "undecided": the game is over and the answer is no.
     *
     * It needs stating because ESPN encodes a tie as `winner: false` on both
     * competitors, so the naive read of "was this team the winner?" quietly
     * scores a tie as a successful pick.
     */
    public static function checkPick(Schedule $schedule, string $team): int
    {
        $game = $schedule->gameFor($team);
        if ($game === null || !$game->isCompleted()) {
            return self::PICK_UNDECIDED;
        }

        if ($game->isTie()) {
            return self::PICK_INCORRECT;
        }

        return $game->winner() === $team ? self::PICK_INCORRECT : self::PICK_CORRECT;
    }
}
