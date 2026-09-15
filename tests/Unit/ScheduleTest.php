<?php

namespace LoserPool\Tests\Unit;

use LoserPool\Nfl\Schedule;
use LoserPool\Pool\SeasonConfig;
use LoserPool\Tests\FixtureLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/*
 * Parsing ESPN payloads into Games.
 */
final class ScheduleTest extends TestCase
{
    public function testParsesEveryGameFromAnUntrimmedRealResponse(): void
    {
        $schedule = FixtureLoader::schedule('2026-w01-full-response');

        /* Guards against the trimmed fixtures hiding a parser/shape mismatch. */
        $this->assertCount(16, $schedule->games());
        $this->assertCount(32, $schedule->teamsPlaying());
    }

    /*
     * The reason the deadline rule is expressed in local days.
     *
     * The 2026 opener kicks off at 00:20 UTC on Sept 10, which is Wednesday
     * evening in Chicago. Read as UTC it looks like Thursday -- and a rule
     * written as "no Thursday games" would leave the openers pickable.
     */
    public function testKickoffIsConvertedToThePoolTimezone(): void
    {
        $schedule = FixtureLoader::schedule('2026-w01');
        $opener = $schedule->gameFor('Seattle Seahawks');

        $this->assertNotNull($opener);
        $this->assertSame('Wed', $opener->kickoffDay());
        $this->assertSame('2026-09-09 19:20', $opener->kickoff()->format('Y-m-d H:i'));
    }

    public function testTeamsOnByeAreAbsentFromTheWeek(): void
    {
        $schedule = FixtureLoader::schedule('2026-w06');

        $this->assertCount(14, $schedule->games());
        $this->assertCount(28, $schedule->teamsPlaying());
        $this->assertNull($schedule->gameFor('Detroit Lions'));
        $this->assertNotNull($schedule->gameFor('Kansas City Chiefs'));
    }

    public function testReadsWinnerAndCompletionFromFinishedGames(): void
    {
        $game = FixtureLoader::schedule('2025-w03')->gameFor('Buffalo Bills');

        $this->assertNotNull($game);
        $this->assertTrue($game->isCompleted());
        $this->assertFalse($game->isTie());
        $this->assertSame('Buffalo Bills', $game->winner());
        $this->assertTrue($game->involves('Miami Dolphins'));
    }

    public function testScheduledGamesHaveNoWinner(): void
    {
        $game = FixtureLoader::schedule('2026-w01')->gameFor('Kansas City Chiefs');

        $this->assertNotNull($game);
        $this->assertFalse($game->isCompleted());
        $this->assertNull($game->winner());
        $this->assertFalse($game->isTie());
    }

    /* A finished game where nobody is flagged the winner is a tie. */
    public function testFinishedGameWithNoWinnerIsATie(): void
    {
        $schedule = Schedule::fromEspnPayload(
            self::payloadFor('2026-01-04T18:00Z', true, ['Chicago Bears' => false, 'Detroit Lions' => false]),
            SeasonConfig::timezone()
        );

        $game = $schedule->gameFor('Chicago Bears');
        $this->assertNotNull($game);
        $this->assertTrue($game->isTie());
        $this->assertNull($game->winner());
    }

    /**
     * Malformed input must degrade to an empty schedule, never an exception:
     * the pool's front page renders these during game week.
     *
     * @dataProvider malformedPayloads
     */
    #[DataProvider('malformedPayloads')]
    public function testMalformedPayloadsYieldAnEmptySchedule(?array $payload): void
    {
        $schedule = Schedule::fromEspnPayload($payload, SeasonConfig::timezone());

        $this->assertTrue($schedule->isEmpty());
        $this->assertSame([], $schedule->teamsPlaying());
        $this->assertNull($schedule->gameFor('Chicago Bears'));
    }

    public static function malformedPayloads(): array
    {
        return [
            'null payload' => [null],
            'no events key' => [['season' => ['year' => 2026]]],
            'events not a list' => [['events' => 'nope']],
            'event missing date' => [['events' => [['competitions' => [[]]]]]],
            'event missing competitions' => [['events' => [['date' => '2026-09-13T17:00Z']]]],
            'unparseable date' => [['events' => [[
                'date' => 'not-a-date',
                'competitions' => [['competitors' => [['team' => ['displayName' => 'Chicago Bears']]]]],
            ]]]],
            'competitor missing team name' => [['events' => [[
                'date' => '2026-09-13T17:00Z',
                'competitions' => [['competitors' => [['winner' => true]]]],
            ]]]],
        ];
    }

    /* Partial payloads keep the games that do parse. */
    public function testOneBadEventDoesNotDiscardTheRest(): void
    {
        $payload = ['events' => [
            ['date' => 'garbage', 'competitions' => [['competitors' => []]]],
            [
                'date' => '2026-09-13T17:00Z',
                'competitions' => [[
                    'status' => ['type' => ['completed' => false]],
                    'competitors' => [
                        ['team' => ['displayName' => 'Chicago Bears']],
                        ['team' => ['displayName' => 'Detroit Lions']],
                    ],
                ]],
            ],
        ]];

        $schedule = Schedule::fromEspnPayload($payload, SeasonConfig::timezone());

        $this->assertCount(1, $schedule->games());
        $this->assertNotNull($schedule->gameFor('Chicago Bears'));
    }

    /** @param array<string,bool> $competitors team => winner flag */
    private static function payloadFor(string $date, bool $completed, array $competitors): array
    {
        $entries = [];
        foreach ($competitors as $team => $won) {
            $entries[] = ['team' => ['displayName' => $team], 'winner' => $won];
        }

        return ['events' => [[
            'date' => $date,
            'competitions' => [[
                'status' => ['type' => ['completed' => $completed]],
                'competitors' => $entries,
            ]],
        ]]];
    }
}
