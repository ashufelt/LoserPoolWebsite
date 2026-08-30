#!/usr/bin/env php
<?php

/*
 * Registers a player from the command line.
 *
 * The site closes registration when week one locks, which is the rule the pool
 * runs on. This is the way round it, and it exists because the rule has one
 * predictable exception: somebody paid on time and did not get themselves
 * entered, and the commissioner decides they are in. That is a judgement about
 * money, made off the site, exactly like a buy-back.
 *
 * It bypasses the season gate and nothing else. The username still has to be a
 * username and the PIN still has to be four digits, because those are what the
 * rest of the pool relies on.
 *
 *     php bin/register.php <name> <email> <username> <pin>
 */

require_once __DIR__ . '/../src/bootstrap.php';

use LoserPool\Storage\PoolStore;

if (($argv[1] ?? '--help') === '--help' || count($argv) !== 5) {
    fwrite(STDERR, "usage: php bin/register.php <name> <email> <username> <pin>\n");
    exit(1);
}

[, $name, $email, $username, $pin] = $argv;

if (preg_match('/^\w{3,20}$/', $username) !== 1) {
    fwrite(STDERR, "Username must be 3 to 20 letters, numbers or underscores: $username\n");
    exit(1);
}

if (preg_match('/^\d{4}$/', $pin) !== 1) {
    fwrite(STDERR, "PIN must be four digits.\n");
    exit(1);
}

$result = lp_store()->addUser($name, $email, $username, $pin);

if ($result === PoolStore::USER_EXISTS) {
    fwrite(STDERR, "Username already taken: $username\n");
    exit(1);
}

if ($result !== PoolStore::OK) {
    fwrite(STDERR, "Could not register $username.\n");
    exit(1);
}

echo "Registered $username. They can pick with the PIN you set.\n";
