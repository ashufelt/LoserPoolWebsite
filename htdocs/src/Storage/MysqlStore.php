<?php

namespace LoserPool\Storage;

use SqlAccess\SqlAccessController;

/*
 * MySQL implementation: a thin adapter over the existing SqlAccessController.
 *
 * Deliberately thin. The MySQL path is the one piece of this codebase that
 * cannot be run or tested here -- the available PHP build has pdo_sqlite but
 * no mysqli -- so the safest possible change is one that leaves its queries
 * and connection handling untouched and only renames the door.
 */
final class MysqlStore implements PoolStore
{
    private SqlAccessController $controller;

    public function __construct(?SqlAccessController $controller = null)
    {
        $this->controller = $controller ?? new SqlAccessController();
    }

    public function allUsernames(): array
    {
        return $this->controller->get_user_table();
    }

    public function userExists(string $username): bool
    {
        return $this->controller->user_exists($username);
    }

    public function verifyPin(string $username, int $pin): bool
    {
        /* get_user_pin returns -1 for an unknown user or a missing PIN. */
        $stored = $this->controller->get_user_pin($username);
        return $stored !== -1 && $stored === $pin;
    }

    public function addUser(string $name, string $email, string $username, int $pin): int
    {
        return $this->controller->add_user($name, $email, $username, $pin);
    }

    public function picksFor(string $username): array
    {
        return $this->controller->get_user_all_picks($username);
    }

    public function savePick(string $username, string $team, int $week): int
    {
        return $this->controller->add_pick($username, $team, $week);
    }
}
