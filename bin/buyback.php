#!/usr/bin/env php
<?php

/*
 * Records that a player bought back in after week one.
 *
 * Buying back is a commissioner's decision made outside the site -- money
 * changes hands somewhere else -- and the picks cannot reveal it, since an
 * eliminated player can still submit picks. So it is recorded here by hand.
 *
 *     php bin/buyback.php <username>
 *     php bin/buyback.php --list
 */

require_once __DIR__ . '/../src/bootstrap.php';

use LoserPool\Storage\PoolStore;

$store = lp_store();
$argument = $argv[1] ?? null;

if ($argument === null || $argument === '--help') {
    fwrite(STDERR, "usage: php bin/buyback.php <username>\n       php bin/buyback.php --list\n");
    exit(1);
}

if ($argument === '--list') {
    $buybacks = $store->buybacks();
    if ($buybacks === []) {
        echo "No buy-backs recorded.\n";
        exit(0);
    }
    foreach ($buybacks as $username) {
        echo "  $username\n";
    }
    exit(0);
}

$result = $store->grantBuyback($argument);
if ($result === PoolStore::NO_SUCH_USER) {
    fwrite(STDERR, "No such user: $argument\n");
    exit(1);
}

echo "Recorded buy-back for $argument. A week 1 loss no longer eliminates them.\n";
