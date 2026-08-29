#!/usr/bin/env php
<?php

/*
 * Answers one question: can this machine actually reach ESPN?
 *
 * It matters because the failure is silent by design. EspnClient falls back
 * through cache and committed snapshots, so a site that cannot reach ESPN at
 * all still renders correctly -- it just stops learning results, and the picks
 * grid never colours in. Without this check that looks like "the results
 * aren't updating yet" rather than "outbound HTTP is blocked".
 *
 * Run it on the web host:
 *
 *     php bin/espn-check.php
 *
 * Exit status is 0 only if live data was actually fetched.
 */

require_once __DIR__ . '/../src/autoload.php';

use LoserPool\Nfl\EspnClient;
use LoserPool\Pool\SeasonConfig;

$cacheDir = SeasonConfig::cacheDir();
$snapshotDir = SeasonConfig::snapshotDir();

/* TTL 0 so this always attempts the network rather than answering from cache. */
$client = new EspnClient($cacheDir, SeasonConfig::timezone(), $snapshotDir, 0, SeasonConfig::HTTP_TIMEOUT_SECONDS);

echo "Loser Pool connectivity check\n";
echo str_repeat('-', 46), "\n";
printf("PHP version      %s\n", PHP_VERSION);
printf("curl available   %s\n", function_exists('curl_init') ? 'yes' : 'no (falls back to file_get_contents)');
printf("allow_url_fopen  %s\n", ini_get('allow_url_fopen') ? 'on' : 'off');
printf("cache writable   %s\n", is_writable(is_dir($cacheDir) ? $cacheDir : dirname($cacheDir)) ? 'yes' : 'NO');

$schedule = $client->weekSchedule(SeasonConfig::YEAR, 1);
$sources = $client->sources();
$source = reset($sources) ?: 'unavailable';
$status = $client->lastHttpStatus();

echo str_repeat('-', 46), "\n";
printf("HTTP status      %s\n", $status === null ? 'no request made' : $status);
printf("Data source      %s\n", $source);
printf("Week 1 games     %d\n", count($schedule->games()));

$snapshots = glob($snapshotDir . '/*.json') ?: [];
printf("Snapshots        %d files\n", count($snapshots));
if ($snapshots !== []) {
    $newest = max(array_map('filemtime', $snapshots));
    $ageDays = (int) floor((time() - $newest) / 86400);
    printf("Snapshot age     %d day(s)\n", $ageDays);
    if ($ageDays > 21) {
        echo "  ! Snapshots are stale. Late-season kickoff times are provisional\n";
        echo "    and get flexed; re-run bin/refresh-snapshots.php.\n";
    }
}

echo str_repeat('-', 46), "\n";
if ($source === 'live') {
    echo "OK: live data reached ESPN. Results will update on their own.\n";
    exit(0);
}

echo "PROBLEM: no live data.\n";
echo "The site will still work, using ";
echo $source === 'unavailable' ? "no schedule data at all" : "the $source copy";
echo ",\nbut RESULTS WILL NEVER UPDATE because only live data carries scores.\n\n";
echo "Likely causes: the host blocks outbound HTTP, or ESPN rejected the\n";
echo "request (it 403s some user agents).\n";
exit(1);
