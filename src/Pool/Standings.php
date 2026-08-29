<?php

namespace LoserPool\Pool;

/*
 * Who is still in.
 *
 * A player is out the first time a pick fails: the team they picked won, or
 * tied. Weeks that have not finished do not count either way.
 *
 * Week one is special, because the pool allows buying back in after it. A
 * week-one loss therefore only eliminates a player who did not buy back, and
 * that is not something the picks can reveal -- an eliminated player can still
 * submit picks, so continuing to play proves nothing. Buy-backs are recorded
 * explicitly and passed in.
 */
final class Standings
{
    public const IN = 'in';
    public const OUT = 'out';

    /**
     * @param array<string,array<int,string>> $allPicks username => (week => team)
     * @param callable $checkPick fn(int $week, string $team): int, a Rules::PICK_* value
     * @param string[] $buybacks usernames who bought back in after week one
     * @param string[] $allUsernames everyone registered, including those who never picked
     *
     * @return array<string,array{status:string,outWeek:?int,correct:int}>
     */
    public static function build(
        array $allPicks,
        callable $checkPick,
        array $buybacks = [],
        array $allUsernames = []
    ): array {
        $usernames = array_unique(array_merge(array_keys($allPicks), $allUsernames));
        sort($usernames, SORT_NATURAL | SORT_FLAG_CASE);

        $boughtBack = array_flip(array_map('strtolower', $buybacks));

        $standings = [];
        foreach ($usernames as $username) {
            $picks = $allPicks[$username] ?? [];
            ksort($picks);

            $correct = 0;
            $outWeek = null;

            foreach ($picks as $week => $team) {
                $result = $checkPick((int) $week, (string) $team);

                if ($result === Rules::PICK_CORRECT) {
                    $correct++;
                    continue;
                }

                if ($result !== Rules::PICK_INCORRECT) {
                    continue; /* not played yet, or no data */
                }

                /* Week one only ends a season for players who did not buy back. */
                if ((int) $week === 1 && isset($boughtBack[strtolower($username)])) {
                    continue;
                }

                $outWeek = (int) $week;
                break;
            }

            $standings[$username] = [
                'status' => $outWeek === null ? self::IN : self::OUT,
                'outWeek' => $outWeek,
                'correct' => $correct,
            ];
        }

        return $standings;
    }

    /** @param array<string,array{status:string,outWeek:?int,correct:int}> $standings */
    public static function stillIn(array $standings): int
    {
        return count(array_filter($standings, static fn($row) => $row['status'] === self::IN));
    }
}
