#!/usr/bin/env php
<?php

/*
 * Copies the pool database to a dated file beside it.
 *
 * VACUUM INTO rather than copy(): the database is served by Apache while this
 * runs, and a plain file copy of a live SQLite database can catch it mid-write
 * and produce a file that will not open. VACUUM INTO takes a read lock and
 * writes a consistent, compacted copy.
 *
 * This protects against the likely failure -- a bad command, a botched delete,
 * a season's picks removed by accident. It does not protect against losing the
 * volume, because it lives on the volume. Fly's scheduled snapshots cover
 * that, and the two are meant to be held together.
 *
 *     php bin/backup.php            # write one, then rotate
 *     php bin/backup.php --list     # what is there
 */

require_once __DIR__ . '/../src/bootstrap.php';

use LoserPool\Pool\SeasonConfig;
use LoserPool\Storage\StoreFactory;

const KEEP = 14;

$database = StoreFactory::sqlitePath();
$directory = dirname($database) . '/backups';

if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    fwrite(STDERR, "Could not create $directory\n");
    exit(1);
}

$existing = glob($directory . '/pool-*.sqlite') ?: [];
rsort($existing);

if (($argv[1] ?? '') === '--list') {
    if ($existing === []) {
        echo "No backups in $directory\n";
        exit(0);
    }
    foreach ($existing as $file) {
        printf("  %s  %s\n", basename($file), number_format((int) filesize($file)) . ' bytes');
    }
    exit(0);
}

if (!is_file($database)) {
    fwrite(STDERR, "No database at $database\n");
    exit(1);
}

$target = sprintf('%s/pool-%s.sqlite', $directory, gmdate('Y-m-d-His'));

/*
 * VACUUM INTO refuses to write over an existing file. Two runs inside the same
 * second are the same backup, so the earlier one goes rather than the command
 * failing -- this runs unattended, and an error nobody reads is worse than a
 * duplicate nobody needed.
 */
if (is_file($target)) {
    unlink($target);
}

try {
    $pdo = new PDO('sqlite:' . $database);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("VACUUM INTO '" . str_replace("'", "''", $target) . "'");
} catch (PDOException $e) {
    fwrite(STDERR, "Backup failed: " . $e->getMessage() . "\n");
    exit(1);
}

/*
 * Opened and counted before the old ones are rotated away. A backup nobody has
 * read is a guess, and this is the cheapest possible check that the file is a
 * database rather than zero bytes with the right name.
 */
try {
    $check = new PDO('sqlite:' . $target);
    $check->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $users = (int) $check->query('SELECT COUNT(*) FROM Users_' . SeasonConfig::TABLE_SUFFIX)->fetchColumn();
    $picks = (int) $check->query('SELECT COUNT(*) FROM Picks_' . SeasonConfig::TABLE_SUFFIX)->fetchColumn();
} catch (PDOException $e) {
    fwrite(STDERR, "Wrote $target but could not read it back: " . $e->getMessage() . "\n");
    exit(1);
}

printf("%s  %s bytes  %d user(s)  %d pick(s)\n",
    basename($target), number_format((int) filesize($target)), $users, $picks);

$all = glob($directory . '/pool-*.sqlite') ?: [];
rsort($all);
foreach (array_slice($all, KEEP) as $stale) {
    unlink($stale);
    echo "  rotated out " . basename($stale) . "\n";
}
