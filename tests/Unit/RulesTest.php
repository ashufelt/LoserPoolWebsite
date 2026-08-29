<?php

namespace LoserPool\Tests\Unit;

use LoserPool\Nfl\Schedule;
use LoserPool\Nfl\Teams;
use LoserPool\Pool\Rules;
use LoserPool\Pool\SeasonConfig;
use LoserPool\Tests\FixtureLoader;
use PHPUnit\Framework\TestCase;

/*
 * The pool rules, checked against real 2025 and 2026 schedule data.
 */
final class RulesTest extends TestCase
{
    private const BLOCKED_DAYS = ['Wed', 'Thu', 'Fri'];

    /*
     * The rule that a naive reading gets wrong.
     *
     * 2026 week 18 is played entirely on Saturday. Expressing the deadline as
     * "nothing before Sunday" would mark all 32 teams ineligible and make the
     * final week unpickable for everyone. Saturday games are pickable.
     */
    public function testSaturdayOnlyWeekLeavesEveryTeamPickable(): void
    {
        $schedule = FixtureLoader::schedule('2026-w18');

        $this->assertCount(16, $schedule->games());
        $this->assertSame(
            [],
            Rules::ineligibleTeams($schedule, Teams::all(), self::BLOCKED_DAYS),
            'Week 18 is Saturday-only; blocking it would leave nobody able to pick.'
        );
    }

    /*
     * The other half of the same rule: the 2026 opener is a Wednesday game, so
     * a literal "no Thursday games" check would miss Seattle and New England.
     */
    public function testWednesdayAndThursdayTeamsAreBlockedInWeekOne(): void
    {
        $ineligible = Rules::ineligibleTeams(
            FixtureLoader::schedule('2026-w01'),
            Teams::all(),
            self::BLOCKED_DAYS
        );

        $this->assertSame([
            'Los Angeles Rams',        // Thursday
            'New England Patriots',    // Wednesday
            'San Francisco 49ers',     // Thursday
            'Seattle Seahawks',        // Wednesday
        ], $ineligible);

        /* Monday night is after the deadline and stays available. */
        $this->assertNotContains('Kansas City Chiefs', $ineligible);
        $this->assertNotContains('Denver Broncos', $ineligible);
    }

    /* Friday games are blocked while Saturday games in the same week are not. */
    public function testFridayIsBlockedButSaturdayIsNotInTheSameWeek(): void
    {
        $ineligible = Rules::ineligibleTeams(
            FixtureLoader::schedule('2026-w16'),
            Teams::all(),
            self::BLOCKED_DAYS
        );

        $this->assertCount(8, $ineligible);
        foreach (['Philadelphia Eagles', 'Houston Texans', 'Chicago Bears', 'Green Bay Packers'] as $blocked) {
            $this->assertContains($blocked, $ineligible);
        }
        /* Saturday teams that week remain pickable. */
        foreach (['Atlanta Falcons', 'Tampa Bay Buccaneers', 'Pittsburgh Steelers', 'Carolina Panthers'] as $allowed) {
            $this->assertNotContains($allowed, $ineligible);
        }
    }

    public function testByeTeamsAreBlockedAlongsideEarlyKickoffs(): void
    {
        $ineligible = Rules::ineligibleTeams(
            FixtureLoader::schedule('2026-w06'),
            Teams::all(),
            self::BLOCKED_DAYS
        );

        $this->assertSame([
            'Cincinnati Bengals',   // bye
            'Denver Broncos',       // Thursday
            'Detroit Lions',        // bye
            'Miami Dolphins',       // bye
            'Minnesota Vikings',    // bye
            'Seattle Seahawks',     // Thursday
        ], $ineligible);
    }

    /*
     * When the schedule cannot be loaded we block nothing.
     *
     * Blocking everything would be indistinguishable from a bye week and would
     * quietly stop the pool; an unrestricted dropdown is the safer failure.
     */
    public function testAnUnavailableScheduleBlocksNothing(): void
    {
        $this->assertSame(
            [],
            Rules::ineligibleTeams(Schedule::emptySchedule(), Teams::all(), self::BLOCKED_DAYS)
        );
    }

    public function testEmptyBlockedDayListLeavesOnlyByes(): void
    {
        $ineligible = Rules::ineligibleTeams(FixtureLoader::schedule('2026-w06'), Teams::all(), []);

        $this->assertSame([
            'Cincinnati Bengals',
            'Detroit Lions',
            'Miami Dolphins',
            'Minnesota Vikings',
        ], $ineligible);
    }

    /* You pick a team to lose, so the team that lost is the correct pick. */
    public function testPickingTheLosingTeamIsCorrect(): void
    {
        $schedule = FixtureLoader::schedule('2025-w03');

        $this->assertSame(Rules::PICK_CORRECT, Rules::checkPick($schedule, 'Miami Dolphins'));
        $this->assertSame(Rules::PICK_CORRECT, Rules::checkPick($schedule, 'Green Bay Packers'));
    }

    public function testPickingTheWinningTeamIsIncorrect(): void
    {
        $schedule = FixtureLoader::schedule('2025-w03');

        $this->assertSame(Rules::PICK_INCORRECT, Rules::checkPick($schedule, 'Buffalo Bills'));
        $this->assertSame(Rules::PICK_INCORRECT, Rules::checkPick($schedule, 'Cleveland Browns'));
    }

    public function testUnplayedAndUnknownTeamsAreUndecided(): void
    {
        $this->assertSame(
            Rules::PICK_UNDECIDED,
            Rules::checkPick(FixtureLoader::schedule('2026-w01'), 'Chicago Bears'),
            'A scheduled game has no result yet.'
        );
        $this->assertSame(
            Rules::PICK_UNDECIDED,
            Rules::checkPick(FixtureLoader::schedule('2026-w06'), 'Detroit Lions'),
            'A team on bye has no game to score.'
        );
        $this->assertSame(
            Rules::PICK_UNDECIDED,
            Rules::checkPick(FixtureLoader::schedule('2025-w03'), 'Not A Real Team')
        );
    }

    /*
     * A tie eliminates the player: you must pick a team that loses, and a team
     * that ties did not lose.
     *
     * The trap is that ESPN encodes a tie as `winner: false` on both
     * competitors, so checking only "was this team the winner?" scores a tie
     * as a successful pick -- silently keeping an eliminated player alive.
     */
    public function testATieEliminatesThePicker(): void
    {
        $payload = ['events' => [[
            'date' => '2026-11-08T18:00Z',
            'competitions' => [[
                'status' => ['type' => ['completed' => true]],
                'competitors' => [
                    ['team' => ['displayName' => 'Chicago Bears'], 'winner' => false],
                    ['team' => ['displayName' => 'Detroit Lions'], 'winner' => false],
                ],
            ]],
        ]]];

        $schedule = Schedule::fromEspnPayload($payload, SeasonConfig::timezone());

        $this->assertSame(Rules::PICK_INCORRECT, Rules::checkPick($schedule, 'Chicago Bears'));
        $this->assertSame(Rules::PICK_INCORRECT, Rules::checkPick($schedule, 'Detroit Lions'));
    }

    /*
     * Team names must match ESPN's displayName exactly -- picks are compared to
     * schedule data as plain strings, with no mapping layer. If this fails, the
     * pool silently stops scoring.
     */
    public function testTeamListMatchesTheNamesEspnUses(): void
    {
        $playing = FixtureLoader::schedule('2026-w01')->teamsPlaying();
        sort($playing);
        $known = Teams::all();
        sort($known);

        $this->assertSame(32, Teams::count());
        $this->assertSame($known, $playing);
    }
}
