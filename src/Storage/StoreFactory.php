<?php

namespace LoserPool\Storage;

use LoserPool\Pool\SeasonConfig;

/*
 * Opens the pool's database.
 *
 * SQLite only. The MySQL implementation was retired with the move off the
 * original shared host: it required a database server to provision, could not
 * be tested here at all, and its credentials were committed in cleartext.
 * Deleting it removes the credential problem rather than rotating around it.
 *
 *   LP_SQLITE_PATH  database file; defaults to var/pool.sqlite
 */
final class StoreFactory
{
    public static function fromEnvironment(): PoolStore
    {
        return SqliteStore::open(self::sqlitePath(), SeasonConfig::TABLE_SUFFIX);
    }

    public static function sqlitePath(): string
    {
        $configured = getenv('LP_SQLITE_PATH');
        return $configured !== false && $configured !== ''
            ? $configured
            : dirname(__DIR__, 2) . '/var/pool.sqlite';
    }
}
