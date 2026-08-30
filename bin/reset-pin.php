#!/usr/bin/env php
<?php

/*
 * Resets a player's PIN.
 *
 * The site cannot do this itself. PINs are stored as bcrypt hashes, so there
 * is nothing to look up and send back, and mailing a replacement needs a mail
 * provider the pool does not have. The footer tells players to write to the
 * commissioner instead, and this is what the commissioner does about it.
 *
 * The registered address is printed first, so whoever is asking can be checked
 * against whoever registered before their PIN is changed.
 *
 *     php bin/reset-pin.php <username>          # a new random PIN
 *     php bin/reset-pin.php <username> <pin>    # one you have chosen
 */

require_once __DIR__ . '/../src/bootstrap.php';

use LoserPool\Storage\PoolStore;

$username = $argv[1] ?? null;

if ($username === null || $username === '--help') {
    fwrite(STDERR, "usage: php bin/reset-pin.php <username> [pin]\n");
    exit(1);
}

$store = lp_store();
$email = $store->emailFor($username);

if ($email === null) {
    fwrite(STDERR, "No such user: $username\n");
    exit(1);
}

$pin = $argv[2] ?? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

if (preg_match('/^\d{4}$/', $pin) !== 1) {
    fwrite(STDERR, "PIN must be four digits.\n");
    exit(1);
}

if ($store->setPin($username, $pin) !== PoolStore::OK) {
    fwrite(STDERR, "Could not reset the PIN for $username.\n");
    exit(1);
}

echo "$username registered with $email\n";
echo "New PIN: $pin\n";
echo "Their previous PIN no longer works. Send this to that address, not to whoever asked.\n";
