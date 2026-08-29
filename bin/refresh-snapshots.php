#!/usr/bin/env php
<?php

/*
 * Regenerates the committed schedule snapshots in htdocs/data/snapshots/.
 *
 * Snapshots are the last rung of the EspnClient degradation ladder: if the web
 * host cannot reach ESPN at all, they are what keeps byes and kickoff times
 * correct. They hold schedule data, not live results, so a stale snapshot
 * costs accuracy in the results grid but never breaks picking.
 *
 * They go stale in two ways, so refresh them periodically:
 *
 *   - Late-season kickoff times are provisional. Weeks 16-18 currently carry
 *     placeholder times (every Saturday game at 23:00Z), and the NFL flexes
 *     games into their real slots during the season. Week 18 in particular is
 *     effectively TBD until close to the date.
 *   - Snapshots record no results. They can only ever answer "who plays when",
 *     never "who won", so on a host with no outbound network the results grid
 *     stays blank however fresh they are.
 *
 * Run once before the season, and again whenever the schedule firms up:
 *
 *     php bin/refresh-snapshots.php [season]
 */

require_once __DIR__ . '/../src/autoload.php';

use LoserPool\Pool\SeasonConfig;

$season = isset($argv[1]) ? (int) $argv[1] : SeasonConfig::YEAR;
$target = SeasonConfig::snapshotDir();

if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
    fwrite(STDERR, "Cannot create $target\n");
    exit(1);
}

$failures = 0;
for ($week = 1; $week <= SeasonConfig::REGULAR_SEASON_WEEKS; $week++) {
    $url = 'https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard?'
        . http_build_query(['dates' => $season, 'seasontype' => 2, 'week' => $week]);

    $body = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 20, 'header' => "User-Agent: " . SeasonConfig::USER_AGENT . "\r\n"],
    ]));

    $payload = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($payload) || !isset($payload['events'])) {
        fwrite(STDERR, sprintf("week %2d: FAILED\n", $week));
        $failures++;
        continue;
    }

    /* Keep only what Schedule actually parses, so diffs stay reviewable. */
    $trimmed = [
        'generated_at' => gmdate('c'),
        'season' => $payload['season'] ?? null,
        'week' => $payload['week'] ?? null,
        'events' => [],
    ];
    foreach ($payload['events'] as $event) {
        if (!isset($event['competitions'][0]['competitors'])) {
            continue;
        }
        $competitors = [];
        foreach ($event['competitions'][0]['competitors'] as $competitor) {
            $competitors[] = [
                'team' => ['displayName' => $competitor['team']['displayName'] ?? null],
                'winner' => $competitor['winner'] ?? null,
                'score' => $competitor['score'] ?? null,
            ];
        }
        $trimmed['events'][] = [
            'id' => $event['id'] ?? null,
            'date' => $event['date'] ?? null,
            'competitions' => [[
                'status' => ['type' => [
                    'name' => $event['competitions'][0]['status']['type']['name'] ?? null,
                    'completed' => $event['competitions'][0]['status']['type']['completed'] ?? false,
                ]],
                'competitors' => $competitors,
            ]],
        ];
    }

    $file = sprintf('%s/nfl-%d-w%02d.json', $target, $season, $week);
    file_put_contents($file, json_encode($trimmed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    printf("week %2d: %2d events -> %s\n", $week, count($trimmed['events']), basename($file));
    usleep(200000);
}

exit($failures === 0 ? 0 : 1);
