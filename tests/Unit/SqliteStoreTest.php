<?php

namespace LoserPool\Tests\Unit;

use LoserPool\Storage\PoolStore;
use LoserPool\Storage\SqliteStore;
use PHPUnit\Framework\TestCase;

/*
 * The first tests the storage layer has ever had.
 *
 * The MySQL implementation cannot be exercised here -- the available PHP build
 * has no mysqli, and there is no database to point it at -- so these run
 * against SQLite in memory, which is also what the container deployment uses.
 */
final class SqliteStoreTest extends TestCase
{
    private SqliteStore $store;

    protected function setUp(): void
    {
        $this->store = SqliteStore::open(':memory:', '26');
    }

    private function register(string $username, int $pin = 1234): int
    {
        return $this->store->addUser('Real Name', 'player@example.com', $username, $pin);
    }

    public function testRegistersAUserAndListsThem(): void
    {
        $this->assertSame(PoolStore::OK, $this->register('dabearsstillsuck'));
        $this->assertTrue($this->store->userExists('dabearsstillsuck'));
        $this->assertSame(['dabearsstillsuck'], $this->store->allUsernames());
    }

    public function testRejectsADuplicateUsername(): void
    {
        $this->register('commish');

        $this->assertSame(PoolStore::USER_EXISTS, $this->register('commish'));
        $this->assertCount(1, $this->store->allUsernames());
    }

    /* Usernames are the pool's identity; "Commish" must not become a second entry. */
    public function testUsernamesAreCaseInsensitive(): void
    {
        $this->register('commish');

        $this->assertSame(PoolStore::USER_EXISTS, $this->register('COMMISH'));
        $this->assertTrue($this->store->userExists('Commish'));
    }

    public function testVerifiesTheCorrectPin(): void
    {
        $this->register('joeg', 4321);

        $this->assertTrue($this->store->verifyPin('joeg', 4321));
        $this->assertFalse($this->store->verifyPin('joeg', 1111));
    }

    /*
     * An unknown user must fail every PIN, including 0.
     *
     * This is the sharp edge the interface exists to hide: the old code read a
     * PIN out and compared it at the call site, and PHP evaluates `0 != null`
     * as false -- so a null "no such user" sentinel would have authenticated
     * anyone who guessed the PIN 0000 against a username that does not exist.
     */
    public function testUnknownUserFailsEveryPinIncludingZero(): void
    {
        $this->assertFalse($this->store->verifyPin('nobody', 0));
        $this->assertFalse($this->store->verifyPin('nobody', 1234));
        $this->assertFalse($this->store->verifyPin('', 0));
    }

    public function testRecordsAndReadsBackPicks(): void
    {
        $this->register('joeg');

        $this->assertSame(PoolStore::OK, $this->store->savePick('joeg', 'Chicago Bears', 1));
        $this->assertSame(PoolStore::OK, $this->store->savePick('joeg', 'New York Giants', 2));
        $this->assertSame([1 => 'Chicago Bears', 2 => 'New York Giants'], $this->store->picksFor('joeg'));
    }

    /* Changing your mind replaces the week's pick rather than adding one. */
    public function testResubmittingAWeekReplacesThatPick(): void
    {
        $this->register('joeg');
        $this->store->savePick('joeg', 'Chicago Bears', 1);

        $this->store->savePick('joeg', 'Carolina Panthers', 1);

        $this->assertSame([1 => 'Carolina Panthers'], $this->store->picksFor('joeg'));
    }

    /*
     * The old implementation read "is there a pick for this week?" and then
     * chose INSERT or UPDATE. Two submissions arriving together both saw no
     * pick and both inserted, leaving one player with two picks for one week.
     * A unique key makes that unrepresentable.
     */
    public function testAWeekCannotHoldTwoPicks(): void
    {
        $this->register('joeg');

        for ($i = 0; $i < 5; $i++) {
            $this->store->savePick('joeg', 'Team ' . $i, 3);
        }

        $picks = $this->store->picksFor('joeg');
        $this->assertCount(1, $picks);
        $this->assertSame('Team 4', $picks[3]);
    }

    public function testWillNotRecordAPickForAnUnknownUser(): void
    {
        $this->assertSame(PoolStore::NO_SUCH_USER, $this->store->savePick('ghost', 'Chicago Bears', 1));
        $this->assertSame([], $this->store->picksFor('ghost'));
    }

    public function testPlayersPicksAreKeptSeparate(): void
    {
        $this->register('joeg');
        $this->register('sarah_k');
        $this->store->savePick('joeg', 'Chicago Bears', 1);
        $this->store->savePick('sarah_k', 'Las Vegas Raiders', 1);

        $this->assertSame([1 => 'Chicago Bears'], $this->store->picksFor('joeg'));
        $this->assertSame([1 => 'Las Vegas Raiders'], $this->store->picksFor('sarah_k'));
    }

    public function testANewPlayerHasNoPicks(): void
    {
        $this->register('newcomer');

        $this->assertSame([], $this->store->picksFor('newcomer'));
    }

    /* Season tables are separate, so last year's entries cannot leak in. */
    public function testSeasonsAreStoredSeparately(): void
    {
        $this->register('joeg');
        $this->store->savePick('joeg', 'Chicago Bears', 1);

        $nextSeason = SqliteStore::open(':memory:', '27');

        $this->assertSame([], $nextSeason->allUsernames());
        $this->assertFalse($nextSeason->userExists('joeg'));
    }
}
