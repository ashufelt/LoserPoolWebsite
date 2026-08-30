<?php

namespace LoserPool\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use LoserPool\Storage\SqliteStore;
use LoserPool\Tests\FakeScheduleSource;
use LoserPool\Tests\AssertsTeamOptions;
use LoserPool\Tests\FixtureLoader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Handlers/pick_handler.php';
require_once __DIR__ . '/../../src/Handlers/user_handler.php';
require_once __DIR__ . '/../../src/Handlers/team_handler.php';

use function PickHandling\ph_add_pick;
use function PickHandling\ph_get_picks_html_table;
use function PickHandling\ph_get_user_picks_html;
use function UserHandling\uh_add_user;
use function UserHandling\uh_get_user_option_list_html;
use function TeamHandler\get_team_options_html;

/*
 * The path players actually take: register, pick, change your mind, get
 * stopped by the rules.
 *
 * None of this had ever been executed anywhere. It needs a database, and the
 * only implementation was MySQL, which cannot run here. Against an in-memory
 * SQLite store it runs in milliseconds.
 *
 * The clock is frozen because two of these rules are calendar-dependent: picks
 * lock on Sunday and Monday, and other players' picks stay hidden until then.
 * Left to the real clock these assertions would pass or fail according to the
 * day the suite happened to run.
 */
final class PickFlowTest extends TestCase
{
    use AssertsTeamOptions;

    private const PRESEASON = '2026-09-01 10:00:00';
    private const IN_SEASON_TUESDAY = '2026-09-22 10:00:00';
    private const IN_SEASON_SUNDAY = '2026-09-27 13:00:00';

    private SqliteStore $store;

    protected function setUp(): void
    {
        $this->store = SqliteStore::open(':memory:', '26');
        lp_store($this->store);

        lp_schedule_source(new FakeScheduleSource(
            [3 => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 3]
        ));

        $this->freeze(self::IN_SEASON_TUESDAY);
    }

    protected function tearDown(): void
    {
        lp_clock(null, true);
    }

    private function freeze(string $when): void
    {
        lp_clock(new DateTimeImmutable($when, new DateTimeZone('America/Chicago')));
    }

    /*
     * Registration closes when week one locks, and most of these tests run in
     * a later week, so registering happens where a player would actually have
     * done it: before the season started. The clock is put back afterwards.
     */
    private function registerJoe(string $pin = '1234'): bool
    {
        return $this->registerBeforeKickoff('Joe G', 'joe@example.com', 'joeg', $pin);
    }

    /* Point the season at a given week and moment. */
    private function withWeek(int $week, string $when): void
    {
        lp_schedule_source(new FakeScheduleSource(
            [$week => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => $week]
        ));
        $this->freeze($when);
        get_current_week(true);
    }

    private function registerBeforeKickoff(string $name, string $email, string $username, string $pin): bool
    {
        $now = lp_clock();
        $this->freeze(self::PRESEASON);
        $registered = uh_add_user($name, $email, $username, $pin, $pin);
        lp_clock($now);
        get_current_week(true);

        return $registered;
    }

    public function testTheWholeHappyPath(): void
    {
        $this->assertTrue($this->registerJoe());
        $this->assertStringContainsString('joeg', uh_get_user_option_list_html());

        $this->assertStringContainsString('Chicago Bears', ph_add_pick('joeg', 'Chicago Bears', '1234'));
        $this->assertSame([3 => 'Chicago Bears'], $this->store->picksFor('joeg'));
    }

    public function testRegistrationRequiresMatchingPins(): void
    {
        $this->assertFalse(uh_add_user('Joe G', 'joe@example.com', 'joeg', '1234', '9999'));
        $this->assertSame([], $this->store->allUsernames());
    }

    public function testCannotPickWithTheWrongPin(): void
    {
        $this->registerJoe();

        $this->assertStringContainsString('not valid', ph_add_pick('joeg', 'Chicago Bears', '9999'));
        $this->assertSame([], $this->store->picksFor('joeg'));
    }

    public function testCannotPickAsAUserWhoDoesNotExist(): void
    {
        $this->assertStringContainsString('does not exist', ph_add_pick('ghost', 'Chicago Bears', '1234'));
    }

    /* The pool's central rule. */
    public function testCannotRepeatATeamAcrossWeeks(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        lp_schedule_source(new FakeScheduleSource(
            [4 => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 4]
        ));

        $this->assertStringContainsString('Cannot repeat', ph_add_pick('joeg', 'Chicago Bears', '1234'));
        $this->assertSame([3 => 'Chicago Bears'], $this->store->picksFor('joeg'));
    }

    /* Resubmitting the same week is how you change your mind, not a repeat. */
    public function testResubmittingTheSameWeekReplacesThePick(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        $this->assertStringContainsString('Carolina Panthers', ph_add_pick('joeg', 'Carolina Panthers', '1234'));
        $this->assertSame([3 => 'Carolina Panthers'], $this->store->picksFor('joeg'));
    }

    public function testPicksAreLockedOnSunday(): void
    {
        $this->registerJoe();
        $this->freeze(self::IN_SEASON_SUNDAY);

        $this->assertStringContainsString("Picks are locked", ph_add_pick('joeg', 'Chicago Bears', '1234'));
        $this->assertSame([], $this->store->picksFor('joeg'));
    }

    /*
     * Regression: the lock used to be `is_sunday_or_monday() && date('z') > 245`,
     * which silently stopped applying in January when the day of year resets --
     * so picks stayed open during weeks 17 and 18.
     */
    public function testTheLockStillAppliesInJanuary(): void
    {
        $this->registerJoe();
        $this->freeze('2027-01-03 13:00:00'); // a Sunday, day-of-year 2

        $this->assertStringContainsString("Picks are locked", ph_add_pick('joeg', 'Chicago Bears', '1234'));
    }

    public function testPicksCanBeMadeBeforeTheSeasonStarts(): void
    {
        $this->registerJoe();
        $this->freeze('2026-08-30 13:00:00'); // a Sunday, but preseason

        $this->assertStringContainsString('Chicago Bears', ph_add_pick('joeg', 'Chicago Bears', '1234'));
    }

    /*
     * The dropdown disables these, which stops a person clicking one and is
     * worth nothing against a request that did not come from the dropdown.
     * The rule that decides who survives a week has to hold at the handler.
     */
    public function testATeamPlayingBeforeTheDeadlineIsRejectedOnSubmit(): void
    {
        $this->registerJoe();

        /* Week 1 2026 opens on a Wednesday, and Seattle plays in it. */
        $result = ph_add_pick('joeg', 'Seattle Seahawks', '1234');

        $this->assertStringContainsString('cannot be picked this week', $result);
        $this->assertStringContainsString('Plays Wednesday', $result);
        $this->assertSame([], lp_store()->picksFor('joeg'), 'nothing was stored');
    }

    public function testATeamOnByeIsRejectedOnSubmit(): void
    {
        $this->registerJoe();

        lp_schedule_source(new FakeScheduleSource(
            [6 => FixtureLoader::schedule('2026-w06')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 6]
        ));

        $result = ph_add_pick('joeg', 'Cincinnati Bengals', '1234');

        $this->assertStringContainsString('Bye week', $result);
        $this->assertSame([], lp_store()->picksFor('joeg'), 'nothing was stored');
    }

    /* A name nobody plays under would be stored and then scored forever as
       undecided, because no schedule will ever contain it. */
    public function testANameThatIsNotATeamIsRejectedOnSubmit(): void
    {
        $this->registerJoe();

        $result = ph_add_pick('joeg', 'Chicago Bears ', '1234');

        $this->assertStringContainsString('Not a team in this league', $result);
        $this->assertSame([], lp_store()->picksFor('joeg'), 'nothing was stored');
    }

    /*
     * Changing your mind is explicitly allowed, and changing it back to what
     * it already was is the same operation. The no-repeat rule is about the
     * other weeks: counting this week's own pick made a team a repeat of
     * itself, so pressing Submit twice reported "already used this season" for
     * a team used nowhere but here.
     */
    public function testResubmittingTheSameTeamForTheSameWeekIsNotARepeat(): void
    {
        $this->registerJoe();

        $this->assertStringContainsString('Chicago Bears', ph_add_pick('joeg', 'Chicago Bears', '1234'));
        $second = ph_add_pick('joeg', 'Chicago Bears', '1234');

        $this->assertStringNotContainsString('Cannot repeat', $second);
        $this->assertStringContainsString('Chicago Bears', $second);
        $this->assertSame(['Chicago Bears'], array_values(lp_store()->picksFor('joeg')));
    }

    /* Changing to a different team and back again is the same case. */
    public function testAPickCanBeChangedAndChangedBack(): void
    {
        $this->registerJoe();

        ph_add_pick('joeg', 'Chicago Bears', '1234');
        ph_add_pick('joeg', 'Kansas City Chiefs', '1234');
        $back = ph_add_pick('joeg', 'Chicago Bears', '1234');

        $this->assertStringNotContainsString('Cannot repeat', $back);
        $this->assertSame(['Chicago Bears'], array_values(lp_store()->picksFor('joeg')));
    }

    /*
     * A survivor pool cannot take entrants once it is running. Joining in week
     * three would put a player with no losses level with someone who has
     * survived three weeks.
     */
    public function testRegistrationClosesWhenWeekOneLocksAndStaysClosed(): void
    {
        $this->freeze(self::PRESEASON);
        $this->assertTrue(lp_registration_is_open(), 'open before the season starts');

        /* Tuesday of week 1: the pool is open for business. */
        $this->withWeek(1, '2026-09-08 10:00:00');
        $this->assertTrue(lp_registration_is_open(), 'open in week 1 while picks are open');

        /* Sunday of week 1, when picks lock. */
        $this->withWeek(1, '2026-09-13 13:00:00');
        $this->assertFalse(lp_registration_is_open(), 'closed once week 1 locks');

        /*
         * Tuesday of week 2. Picks are open again, which is why this cannot
         * just ask whether picks are locked: that is true on Sunday and Monday
         * of every week and false again the next day.
         */
        $this->withWeek(2, '2026-09-15 10:00:00');
        $this->assertFalse(lp_picks_are_locked(), 'picks have reopened');
        $this->assertFalse(lp_registration_is_open(), 'registration has not');
    }

    public function testARegistrationAfterWeekOneIsRefusedWithTheReason(): void
    {
        $this->withWeek(2, '2026-09-15 10:00:00');

        $problem = null;
        $this->assertFalse(uh_add_user('Late', 'late@example.com', 'latecomer', '1234', '1234', $problem));
        $this->assertStringContainsString('closed when week 1 locked', (string) $problem);
        $this->assertFalse(lp_store()->userExists('latecomer'), 'nothing was created');
    }

    /*
     * A refusal has to say which rule it broke. Every one of these used to
     * produce the same sentence listing everything that might have been wrong.
     */
    public function testARefusedRegistrationSaysWhichRuleItBroke(): void
    {
        $this->freeze(self::PRESEASON);

        $cases = [
            'mismatched PINs' => [['Joe', 'joe@example.com', 'joeg', '1234', '9999'], 'do not match'],
            'a PIN that is not four digits' => [['Joe', 'joe@example.com', 'joeg', '12', '12'], 'four digits'],
            'a username with a space' => [['Joe', 'joe@example.com', 'joe g', '1234', '1234'], 'no spaces'],
            'a username of two characters' => [['Joe', 'joe@example.com', 'jo', '1234', '1234'], '3 to 20'],
            'a missing email' => [['Joe', '', 'joeg', '1234', '1234'], 'every box'],
        ];

        foreach ($cases as $label => [$args, $expected]) {
            $problem = null;
            $args[] = &$problem;
            $this->assertFalse(uh_add_user(...$args), $label . ' is refused');
            $this->assertStringContainsString($expected, (string) $problem, $label);
            unset($problem);
        }
    }

    /* A name is displayed, never used as a key, so punctuation in it is fine. */
    public function testANameWithPunctuationCanRegister(): void
    {
        $this->freeze(self::PRESEASON);
        $problem = null;

        $this->assertTrue(
            uh_add_user("Sean O'Brien-Smith Jr.", 'sean@example.com', 'seanob', '1234', '1234', $problem),
            (string) $problem
        );
    }

    public function testATakenUsernameSaysSoRatherThanListingEveryRule(): void
    {
        $this->registerJoe();
        $this->freeze(self::PRESEASON);
        $problem = null;

        $this->assertFalse(uh_add_user('Joe', 'other@example.com', 'joeg', '1234', '1234', $problem));
        $this->assertStringContainsString('already taken', (string) $problem);
    }

    /* Other players' current-week picks are concealed until the slate starts. */
    public function testOtherPlayersPicksAreHiddenUntilLock(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        $table = ph_get_picks_html_table();
        $this->assertStringContainsString('Submitted', $table);
        $this->assertStringNotContainsString('Chicago Bears', $table);
    }

    /*
     * Before the deadline the table has to answer "who still needs to pick",
     * and an empty cell could not: it looked identical to a column with no
     * data in it. The pick itself stays hidden, only the fact of it is public.
     */
    public function testTheTableSaysWhoHasNotPickedYet(): void
    {
        $this->registerJoe();
        $this->assertSame(0, lp_store()->addUser('Ada', 'ada@example.com', 'ada', '1234'));

        ph_add_pick('joeg', 'Chicago Bears', '1234');

        $table = ph_get_picks_html_table();

        $this->assertStringContainsString('Submitted', $table, 'joeg is in');
        $this->assertStringContainsString('Not in yet', $table, 'ada is not');
        $this->assertStringNotContainsString('Chicago Bears', $table, 'the pick itself stays hidden');
    }

    /* Once picks are locked, the outstanding ones read as missed, not pending. */
    public function testAMissedWeekReadsAsNoPickOnceLocked(): void
    {
        $this->registerJoe();
        $this->freeze(self::IN_SEASON_SUNDAY);

        $this->assertStringContainsString('No pick', ph_get_picks_html_table());
    }

    public function testPicksAreRevealedOnceLocked(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        $this->freeze(self::IN_SEASON_SUNDAY);

        $this->assertStringContainsString('Chicago Bears', ph_get_picks_html_table());
    }

    /*
     * The dropdown is what enforces the rules in the interface, and it is the
     * one surface a reference sweep missed: deleting the function behind it
     * left every page rendering fine while /teams/all.php returned a fatal
     * error, so nobody could pick at all and CI stayed green.
     */
    public function testTheTeamDropdownOffersEligibleTeams(): void
    {
        $this->registerJoe();

        $options = get_team_options_html('joeg');

        $this->assertStringContainsString('Chicago Bears', $options);
        $this->assertStringContainsString('Kansas City Chiefs', $options);
    }

    /*
     * Unavailable teams are listed but unselectable, with the reason. Omitting
     * them left a player unable to tell a team they had already used from one
     * on a bye, or from one that does not exist.
     */
    public function testTeamsPlayingBeforeTheDeadlineAreShownButUnselectable(): void
    {
        $this->registerJoe();

        $options = get_team_options_html('joeg');

        /* Week 1 2026: a Wednesday opener and a Thursday game. */
        $this->assertTeamUnavailable($options, 'Seattle Seahawks', 'Plays Wednesday');
        $this->assertTeamUnavailable($options, 'New England Patriots', 'Plays Wednesday');
        $this->assertTeamUnavailable($options, 'Los Angeles Rams', 'Plays Thursday');
        $this->assertTeamUnavailable($options, 'San Francisco 49ers', 'Plays Thursday');

        $this->assertTeamSelectable($options, 'Kansas City Chiefs');
    }

    public function testTeamsYouAlreadyUsedAreShownWithTheWeekYouUsedThem(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        lp_schedule_source(new FakeScheduleSource(
            [4 => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 4]
        ));

        $this->assertTeamUnavailable(get_team_options_html('joeg'), 'Chicago Bears', 'Used in week 3');
    }

    /* A bye is a different reason from an early kickoff, and says so. */
    public function testTeamsOnByeAreShownWithTheirOwnReason(): void
    {
        $this->registerJoe();

        lp_schedule_source(new FakeScheduleSource(
            [6 => FixtureLoader::schedule('2026-w06')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 6]
        ));

        $options = get_team_options_html('joeg');

        /* 2026 week 6: four teams on bye, plus a Thursday game. */
        $this->assertTeamUnavailable($options, 'Cincinnati Bengals', 'Bye week');
        $this->assertTeamUnavailable($options, 'Detroit Lions', 'Bye week');
        $this->assertTeamUnavailable($options, 'Seattle Seahawks', 'Plays Thursday');
        $this->assertTeamSelectable($options, 'Chicago Bears');
    }

    /* Every team is listed, whether or not it can be picked. */
    public function testAllTeamsAreListedRegardlessOfAvailability(): void
    {
        $this->registerJoe();

        $options = get_team_options_html('joeg');

        $this->assertSame(32, substr_count($options, '<option'));
    }

    /*
     * Listed, but last. By the middle of a season most of the list is used
     * teams and byes, and interleaving them alphabetically buries the handful
     * that can still be picked behind rows that do nothing.
     */
    public function testUnavailableTeamsAreListedAfterTheSelectableOnes(): void
    {
        $this->registerJoe();

        $options = get_team_options_html('joeg');

        preg_match_all('/<option([^>]*)>([^<]+)<\\/option>/', $options, $matches, PREG_SET_ORDER);
        $this->assertCount(32, $matches);

        $seenUnavailable = false;
        foreach ($matches as $option) {
            if (strpos($option[1], 'disabled') !== false) {
                $seenUnavailable = true;
                continue;
            }
            $this->assertFalse(
                $seenUnavailable,
                $option[2] . ' can be picked but is listed after an unavailable team'
            );
        }

        /* Guard against the assertion passing because nothing was disabled. */
        $this->assertTrue($seenUnavailable, 'week 1 2026 has teams playing before the deadline');
    }

    /* The current pick leads, so a native select with no JavaScript shows it. */
    public function testThisWeeksPickIsTheFirstOptionInTheList(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Kansas City Chiefs', '1234');

        $options = get_team_options_html('joeg');

        preg_match_all('/<option[^>]*>([^<]+)<\\/option>/', $options, $matches);
        $this->assertSame('Kansas City Chiefs', $matches[1][0]);
    }

    /* An unknown player still gets a usable list rather than an error. */
    public function testTheDropdownWorksForAnUnknownUser(): void
    {
        $this->assertStringContainsString('Chicago Bears', get_team_options_html(''));
    }

    /* The grid reports how many players are left, which the endgame depends on. */
    public function testTheStandingsReportWhoIsStillIn(): void
    {
        $this->registerJoe();
        $this->registerBeforeKickoff('Sarah K', 'sarah@example.com', 'sarah_k', '2222');

        $table = ph_get_picks_html_table();

        $this->assertStringContainsString('still in', $table);
        $this->assertStringContainsString('Still in', $table);
        $this->assertStringContainsString('joeg', $table);
        $this->assertStringContainsString('sarah_k', $table);
    }

    /* Buy-backs are recorded, not guessed, so the store has to carry them. */
    public function testBuybacksAreRecordedAgainstRealPlayersOnly(): void
    {
        $this->registerJoe();

        $this->assertSame(\LoserPool\Storage\PoolStore::OK, $this->store->grantBuyback('joeg'));
        $this->assertSame(['joeg'], $this->store->buybacks());

        $this->assertSame(
            \LoserPool\Storage\PoolStore::NO_SUCH_USER,
            $this->store->grantBuyback('ghost')
        );
    }

    /* You can always see your own picks, with your PIN. */
    public function testAPlayerCanSeeTheirOwnPicksWithTheirPin(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        $this->assertStringContainsString('Chicago Bears', ph_get_user_picks_html('joeg', '1234'));
        $this->assertStringContainsString('not valid', ph_get_user_picks_html('joeg', '9999'));
    }
}
