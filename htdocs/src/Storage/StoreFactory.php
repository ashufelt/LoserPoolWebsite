<?php

namespace LoserPool\Storage;

use LoserPool\Pool\SeasonConfig;

/*
 * Chooses the storage engine from the environment.
 *
 * Defaults to MySQL so an unchanged deployment on the existing host keeps
 * behaving exactly as it does today. Our own deployment sets LP_STORE=sqlite
 * and never touches the MySQL credentials at all.
 *
 *   LP_STORE        "mysql" (default) or "sqlite"
 *   LP_SQLITE_PATH  database file; defaults to htdocs/data/pool.sqlite
 */
final class StoreFactory
{
    public static function fromEnvironment(): PoolStore
    {
        $driver = strtolower((string) (getenv('LP_STORE') ?: 'mysql'));

        if ($driver === 'sqlite') {
            return SqliteStore::open(self::sqlitePath(), SeasonConfig::TABLE_SUFFIX);
        }

        /*
         * Required lazily: the controller includes conn_info.php at load time,
         * so a SQLite deployment must never reach this line -- that is what
         * lets it run with no database credentials present at all.
         */
        require_once __DIR__ . '/../../SqlAccess/SqlAccessController.php';
        return new MysqlStore();
    }

    public static function sqlitePath(): string
    {
        $configured = getenv('LP_SQLITE_PATH');
        return $configured !== false && $configured !== ''
            ? $configured
            : __DIR__ . '/../../data/pool.sqlite';
    }
}
