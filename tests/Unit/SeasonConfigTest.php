<?php

namespace LoserPool\Tests\Unit;

use DateTimeZone;
use LoserPool\Pool\SeasonConfig;
use PHPUnit\Framework\TestCase;

/*
 * Guards on the one file that gets edited at season rollover.
 */
final class SeasonConfigTest extends TestCase
{
    /*
     * The table suffix must track the season year.
     *
     * Bumping YEAR while leaving TABLE_SUFFIX behind would silently point the
     * new season at the previous season's tables: every player would appear
     * pre-registered, carrying last year's picks, and the no-repeat rule would
     * reject teams they never picked this year. Nothing would error.
     */
    public function testTableSuffixMatchesTheSeasonYear(): void
    {
        $this->assertSame(
            substr((string) SeasonConfig::YEAR, -2),
            SeasonConfig::TABLE_SUFFIX,
            'TABLE_SUFFIX must be the last two digits of YEAR, or the season reuses old tables.'
        );
    }

    /* Week one must fall inside the configured season, not a leftover year. */
    public function testWeekOneStartsInTheConfiguredSeason(): void
    {
        $this->assertSame(SeasonConfig::YEAR, (int) SeasonConfig::weekOneStart()->format('Y'));
    }

    /* Pool weeks run Tuesday to Monday, so the anchor must be a Tuesday. */
    public function testWeekOneStartsOnATuesday(): void
    {
        $this->assertSame('Tue', SeasonConfig::weekOneStart()->format('D'));
    }

    public function testTimezoneIsValid(): void
    {
        $this->assertInstanceOf(DateTimeZone::class, SeasonConfig::timezone());
        $this->assertSame(SeasonConfig::TIMEZONE, SeasonConfig::timezone()->getName());
    }

    /*
     * Saturday must never join the blocked list: 2026 week 18 is played
     * entirely on Saturday, so blocking it leaves nobody able to pick.
     */
    public function testSaturdayIsNeverBlocked(): void
    {
        $this->assertNotContains('Sat', SeasonConfig::BLOCKED_KICKOFF_DAYS);
        $this->assertContains('Thu', SeasonConfig::BLOCKED_KICKOFF_DAYS);
    }
}
