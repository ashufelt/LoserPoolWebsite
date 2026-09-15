<?php

namespace LoserPool\Tests;

use LoserPool\Nfl\Schedule;
use LoserPool\Pool\SeasonConfig;
use RuntimeException;

/*
 * Fixtures are real ESPN responses, recorded rather than invented.
 *
 * The scenario fixtures are trimmed to the fields the parser reads, so the
 * expectations in a test are legible. One untrimmed response is kept
 * (2026-w01-full-response) specifically to prove the parser copes with the
 * real payload, not just our tidied version of it.
 */
final class FixtureLoader
{
    public static function raw(string $name): string
    {
        $path = __DIR__ . '/fixtures/espn/' . $name . '.json';
        if (!is_readable($path)) {
            throw new RuntimeException("Missing fixture: $path");
        }
        return (string) file_get_contents($path);
    }

    public static function payload(string $name): array
    {
        return json_decode(self::raw($name), true);
    }

    public static function schedule(string $name): Schedule
    {
        return Schedule::fromEspnPayload(self::payload($name), SeasonConfig::timezone());
    }
}
