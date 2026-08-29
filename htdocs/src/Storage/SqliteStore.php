<?php

namespace LoserPool\Storage;

use PDO;
use PDOException;

/*
 * SQLite implementation, via PDO.
 *
 * This exists for two reasons. It is the first storage code the project can
 * actually test -- it runs in memory, so the register/pick/overwrite paths get
 * exercised instead of being assumed -- and it removes the database server
 * from deployment, which is what makes the app portable off the current host.
 *
 * The schema is stricter than the MySQL one it replaces. The original had no
 * NOT NULL, no keys and no uniqueness, and relied on the application to check
 * "does a pick already exist for this week" before choosing UPDATE or INSERT.
 * That check is a race: two submissions in the same moment both see no pick
 * and both insert, and the player quietly has two picks for one week. Here a
 * unique key on (username, week_number) makes that impossible, and the upsert
 * is a single statement.
 */
final class SqliteStore implements PoolStore
{
    private PDO $pdo;
    private string $userTable;
    private string $picksTable;

    public function __construct(PDO $pdo, string $seasonSuffix)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->userTable = 'Users_' . $seasonSuffix;
        $this->picksTable = 'Picks_' . $seasonSuffix;
        $this->createTables();
    }

    /* Opens (and creates) a database file, or ':memory:' for tests. */
    public static function open(string $path, string $seasonSuffix): self
    {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        /* Enforce the foreign key below; SQLite ignores it otherwise. */
        $pdo->exec('PRAGMA foreign_keys = ON');
        /* Let a reader continue while a writer commits -- picks arrive in bursts. */
        if ($path !== ':memory:') {
            $pdo->exec('PRAGMA journal_mode = WAL');
        }
        return new self($pdo, $seasonSuffix);
    }

    private function createTables(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$this->userTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                username TEXT NOT NULL UNIQUE COLLATE NOCASE,
                pin INTEGER NOT NULL
            )"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$this->picksTable} (
                pickid INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL COLLATE NOCASE,
                week_number INTEGER NOT NULL,
                pick TEXT NOT NULL,
                UNIQUE (username, week_number),
                FOREIGN KEY (username) REFERENCES {$this->userTable}(username)
            )"
        );
    }

    public function allUsernames(): array
    {
        $rows = $this->pdo->query("SELECT username FROM {$this->userTable}")->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $rows);
    }

    public function userExists(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->userTable} WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() !== false;
    }

    public function verifyPin(string $username, int $pin): bool
    {
        $stmt = $this->pdo->prepare("SELECT pin FROM {$this->userTable} WHERE username = ?");
        $stmt->execute([$username]);
        $stored = $stmt->fetchColumn();

        /* Unknown user: fail closed, and never let a missing row compare equal. */
        if ($stored === false) {
            return false;
        }

        return (int) $stored === $pin;
    }

    public function addUser(string $name, string $email, string $username, int $pin): int
    {
        if ($this->userExists($username)) {
            return self::USER_EXISTS;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->userTable} (name, email, username, pin) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $username, $pin]);
            return self::OK;
        } catch (PDOException $e) {
            /* Loses a race with a simultaneous registration of the same name. */
            return self::ERROR;
        }
    }

    public function picksFor(string $username): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT week_number, pick FROM {$this->picksTable} WHERE username = ?"
        );
        $stmt->execute([$username]);

        $picks = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $picks[(int) $row['week_number']] = (string) $row['pick'];
        }
        return $picks;
    }

    public function savePick(string $username, string $team, int $week): int
    {
        if (!$this->userExists($username)) {
            return self::NO_SUCH_USER;
        }

        try {
            /*
             * One statement, so changing a pick cannot race with itself. The
             * old read-then-decide approach could insert two picks for a week.
             */
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->picksTable} (username, week_number, pick)
                 VALUES (?, ?, ?)
                 ON CONFLICT (username, week_number) DO UPDATE SET pick = excluded.pick"
            );
            $stmt->execute([$username, $week, $team]);
            return self::OK;
        } catch (PDOException $e) {
            return self::ERROR;
        }
    }
}
