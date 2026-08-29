<?php

namespace LoserPool\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use LoserPool\Storage\SqliteStore;
use LoserPool\Tests\FakeScheduleSource;
use LoserPool\Tests\FixtureLoader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/picks/pick_handler.php';
require_once __DIR__ . '/../../htdocs/users/user_handler.php';
require_once __DIR__ . '/../../htdocs/teams/team_handler.php';

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

    private function registerJoe(string $pin = '1234'): bool
    {
        return uh_add_user('Joe G', 'joe@example.com', 'joeg', $pin, $pin);
    }

    public function testTheWholeHappyPath(): void
    {
        $this->assertTrue($this->registerJoe());
        $this->assertStringContainsString('joeg', uh_get_user_option_list_html());

        $this->assertStringContainsString('successfully', ph_add_pick('joeg', 'Chicago Bears', '1234'));
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

        $this->assertStringContainsString('successfully', ph_add_pick('joeg', 'Carolina Panthers', '1234'));
        $this->assertSame([3 => 'Carolina Panthers'], $this->store->picksFor('joeg'));
    }

    public function testPicksAreLockedOnSunday(): void
    {
        $this->registerJoe();
        $this->freeze(self::IN_SEASON_SUNDAY);

        $this->assertStringContainsString("Can't make a pick", ph_add_pick('joeg', 'Chicago Bears', '1234'));
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

        $this->assertStringContainsString("Can't make a pick", ph_add_pick('joeg', 'Chicago Bears', '1234'));
    }

    public function testPicksCanBeMadeBeforeTheSeasonStarts(): void
    {
        $this->registerJoe();
        $this->freeze('2026-08-30 13:00:00'); // a Sunday, but preseason

        $this->assertStringContainsString('successfully', ph_add_pick('joeg', 'Chicago Bears', '1234'));
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

    public function testTheDropdownHidesTeamsPlayingBeforeTheDeadline(): void
    {
        $this->registerJoe();

        $options = get_team_options_html('joeg');

        /* Week 1 2026: Wednesday and Thursday openers. */
        $this->assertStringNotContainsString('Seattle Seahawks', $options);
        $this->assertStringNotContainsString('New England Patriots', $options);
        $this->assertStringNotContainsString('Los Angeles Rams', $options);
        $this->assertStringNotContainsString('San Francisco 49ers', $options);
    }

    public function testTheDropdownHidesTeamsYouAlreadyPicked(): void
    {
        $this->registerJoe();
        ph_add_pick('joeg', 'Chicago Bears', '1234');

        lp_schedule_source(new FakeScheduleSource(
            [4 => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 4]
        ));

        $this->assertStringNotContainsString('Chicago Bears', get_team_options_html('joeg'));
    }

    /* An unknown player still gets a usable list rather than an error. */
    public function testTheDropdownWorksForAnUnknownUser(): void
    {
        $this->assertStringContainsString('Chicago Bears', get_team_options_html(''));
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
