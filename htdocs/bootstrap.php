<?php

/*
 * Shared per-request wiring.
 *
 * The handlers previously each constructed their own SqlAccessController, so
 * a single page could open three connections to do one job. One store per
 * request is enough, and passing an override is how tests substitute an
 * in-memory database.
 */

require_once __DIR__ . '/src/autoload.php';

use LoserPool\Storage\PoolStore;
use LoserPool\Storage\StoreFactory;

function lp_store(?PoolStore $override = null): PoolStore
{
    static $store = null;

    if ($override !== null) {
        $store = $override;
    }

    if ($store === null) {
        $store = StoreFactory::fromEnvironment();
    }

    return $store;
}
