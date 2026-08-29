<?php

/*
 * Browser-reachable health check: https://<site>/health.php
 *
 * bin/espn-check.php answers the same questions, but it needs shell access and
 * lives outside the docroot, so on a shared host it may be neither uploaded
 * nor runnable. This is the version that works with nothing but a browser.
 *
 * It deliberately reports less than the CLI version -- no PHP version, no
 * extension list -- because this URL is public and none of that is anyone
 * else's business. What it does report is the one thing that is otherwise
 * invisible: whether this server can actually reach ESPN.
 *
 * If it cannot, the site keeps working from committed snapshots, but results
 * never update, because only live data carries scores.
 */

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/../src/week_manager.php';

use LoserPool\Nfl\EspnClient;
use LoserPool\Pool\SeasonConfig;

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$cacheDir = SeasonConfig::cacheDir();
$snapshotDir = SeasonConfig::snapshotDir();

/* TTL 0: always attempt the network, so this reflects reality rather than cache. */
$client = new EspnClient($cacheDir, SeasonConfig::timezone(), $snapshotDir, 0, SeasonConfig::HTTP_TIMEOUT_SECONDS);
$schedule = $client->weekSchedule(SeasonConfig::YEAR, 1);
$sources = $client->sources();
$source = reset($sources) ?: 'unavailable';

$cacheTarget = is_dir($cacheDir) ? $cacheDir : dirname($cacheDir);
$snapshots = glob($snapshotDir . '/*.json') ?: [];
/*
 * Age comes from the recorded generated_at, not the file's mtime: in a
 * container every file is dated to the image build, which would report
 * freshly deployed months-old snapshots as brand new.
 */
$snapshotAgeDays = null;
if ($snapshots !== []) {
    $recorded = json_decode((string) file_get_contents($snapshots[0]), true);
    if (isset($recorded['generated_at'])) {
        $generated = strtotime($recorded['generated_at']);
        if ($generated !== false) {
            $snapshotAgeDays = (int) floor((time() - $generated) / 86400);
        }
    }
}

$problems = [];
if ($source !== 'live') {
    $problems[] = 'Cannot reach ESPN. The site still works, but RESULTS WILL NEVER UPDATE.';
}
if (!is_writable($cacheTarget)) {
    $problems[] = 'Cache directory is not writable. Every page load will re-fetch from ESPN.';
}
if ($snapshotAgeDays !== null && $snapshotAgeDays > 21) {
    $problems[] = "Schedule snapshots are {$snapshotAgeDays} days old. Re-run bin/refresh-snapshots.php.";
}
if ($schedule->isEmpty()) {
    $problems[] = 'No schedule data available from any source.';
}

echo "Loser Pool health\n";
echo str_repeat('=', 40), "\n";
printf("Status         %s\n", $problems === [] ? 'OK' : 'PROBLEM');
printf("Season         %d\n", SeasonConfig::YEAR);
printf("Current week   %d\n", get_current_week());
printf("Schedule data  %s (%d games in week 1)\n", $source, count($schedule->games()));
printf("Outbound HTTP  %s\n", $source === 'live' ? 'working' : 'NOT working');
printf("Cache writable %s\n", is_writable($cacheTarget) ? 'yes' : 'no');
printf("Snapshots      %d files%s\n", count($snapshots),
    $snapshotAgeDays === null ? '' : sprintf(', %d day(s) old', $snapshotAgeDays));

if ($problems !== []) {
    echo "\nProblems\n", str_repeat('-', 40), "\n";
    foreach ($problems as $problem) {
        echo "  - $problem\n";
    }
}
