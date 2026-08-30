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

    private function readColumn(string $column, string $username): string
    {
        $reflection = new \ReflectionProperty(SqliteStore::class, 'pdo');
        $reflection->setAccessible(true);
        $pdo = $reflection->getValue($this->store);
        $stmt = $pdo->prepare('SELECT ' . $column . ' FROM Users_26 WHERE username = ?');
        $stmt->execute([$username]);
        return (string) $stmt->fetchColumn();
    }

    private function register(string $username, string $pin = '1234'): int
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
        $this->register('joeg', '4321');

        $this->assertTrue($this->store->verifyPin('joeg', '4321'));
        $this->assertFalse($this->store->verifyPin('joeg', '1111'));
    }

    /*
     * A dump of the database must not hand over working credentials.
     * Four digits is only 10,000 possibilities, so hashing does not make a PIN
     * strong -- it makes a stolen file useless without brute-forcing each one.
     */
    public function testThePinIsNotStoredInPlaintext(): void
    {
        $this->register('joeg', '4321');

        $pdo = new \PDO('sqlite::memory:');
        $stored = $this->readColumn('pin_hash', 'joeg');

        $this->assertNotSame('4321', $stored);
        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertTrue(password_verify('4321', $stored));
    }

    /*
     * PINs are digit sequences, not numbers. Stored as integers, "0123" and
     * "123" were the same PIN and a leading zero silently vanished.
     */
    public function testLeadingZeroesAreSignificant(): void
    {
        $this->register('joeg', '0123');

        $this->assertTrue($this->store->verifyPin('joeg', '0123'));
        $this->assertFalse($this->store->verifyPin('joeg', '123'));
    }

    public function testAllZeroPinStillWorks(): void
    {
        $this->register('joeg', '0000');

        $this->assertTrue($this->store->verifyPin('joeg', '0000'));
        $this->assertFalse($this->store->verifyPin('joeg', '1234'));
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
        $this->assertFalse($this->store->verifyPin('nobody', '0'));
        $this->assertFalse($this->store->verifyPin('nobody', '0000'));
        $this->assertFalse($this->store->verifyPin('nobody', '1234'));
        $this->assertFalse($this->store->verifyPin('', '0000'));
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

    /* The standings grid reads every player's picks in one go. */
    public function testReadsEveryPlayersPicksAtOnce(): void
    {
        $this->register('joeg');
        $this->register('sarah_k');
        $this->register('nopicks');
        $this->store->savePick('joeg', 'Chicago Bears', 1);
        $this->store->savePick('joeg', 'New York Giants', 2);
        $this->store->savePick('sarah_k', 'Las Vegas Raiders', 1);

        $this->assertSame([
            'joeg' => [1 => 'Chicago Bears', 2 => 'New York Giants'],
            'sarah_k' => [1 => 'Las Vegas Raiders'],
        ], $this->store->allPicks());
    }

    public function testANewPlayerHasNoPicks(): void
    {
        $this->register('newcomer');

        $this->assertSame([], $this->store->picksFor('newcomer'));
    }

    /*
     * A database written before PINs were hashed must upgrade itself, keeping
     * the players who are already in it able to log in.
     */
    public function testUpgradesADatabaseThatStoredPlaintextPins(): void
    {
        $pdo = new \PDO('sqlite::memory:', null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE Users_26 (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT, email TEXT, username TEXT, pin INTEGER)');
        $pdo->exec("INSERT INTO Users_26 (name, email, username, pin)
                    VALUES ('Old Timer', 'old@example.com', 'oldtimer', 4321)");

        $store = new SqliteStore($pdo, '26');

        $this->assertTrue($store->userExists('oldtimer'));
        $this->assertTrue($store->verifyPin('oldtimer', '4321'), 'existing players must still get in');
        $this->assertFalse($store->verifyPin('oldtimer', '1111'));

        $columns = array_column($pdo->query('PRAGMA table_info(Users_26)')->fetchAll(\PDO::FETCH_ASSOC), 'name');
        $this->assertContains('pin_hash', $columns);
        $this->assertNotContains('pin', $columns, 'the plaintext column must be gone, not merely unused');
    }

    /* Migrating twice must not undo the first migration. */
    public function testUpgradingIsIdempotent(): void
    {
        $this->register('joeg', '4321');

        $reflection = new \ReflectionProperty(SqliteStore::class, 'pdo');
        $reflection->setAccessible(true);
        $again = new SqliteStore($reflection->getValue($this->store), '26');

        $this->assertTrue($again->verifyPin('joeg', '4321'));
        $this->assertCount(1, $again->allUsernames());
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
