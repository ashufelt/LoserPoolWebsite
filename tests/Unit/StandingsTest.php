<?php

namespace LoserPool\Tests\Unit;

use LoserPool\Pool\Rules;
use LoserPool\Pool\Standings;
use PHPUnit\Framework\TestCase;

/*
 * Who is still in.
 *
 * This matters beyond curiosity: the pool's endgame rule -- the final four or
 * fewer may split the pot -- cannot be applied if nobody can count who is left.
 */
final class StandingsTest extends TestCase
{
    /** Results keyed "week:team", anything absent counts as not yet played. */
    private function checker(array $results): callable
    {
        return static function (int $week, string $team) use ($results): int {
            return $results[$week . ':' . $team] ?? Rules::PICK_UNDECIDED;
        };
    }

    public function testAPlayerWhoseTeamsAllLostIsStillIn(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears', 2 => 'Giants']],
            $this->checker(['1:Bears' => Rules::PICK_CORRECT, '2:Giants' => Rules::PICK_CORRECT])
        );

        $this->assertSame(Standings::IN, $standings['joeg']['status']);
        $this->assertNull($standings['joeg']['outWeek']);
        $this->assertSame(2, $standings['joeg']['correct']);
    }

    public function testAFailedPickEliminates(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears', 2 => 'Giants', 3 => 'Jets']],
            $this->checker([
                '1:Bears' => Rules::PICK_CORRECT,
                '2:Giants' => Rules::PICK_INCORRECT,
                '3:Jets' => Rules::PICK_CORRECT,
            ])
        );

        $this->assertSame(Standings::OUT, $standings['joeg']['status']);
        $this->assertSame(2, $standings['joeg']['outWeek'], 'the week they went out, not the last one played');
    }

    /* Unplayed weeks decide nothing either way. */
    public function testUndecidedWeeksDoNotEliminate(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears', 2 => 'Giants']],
            $this->checker(['1:Bears' => Rules::PICK_CORRECT])
        );

        $this->assertSame(Standings::IN, $standings['joeg']['status']);
    }

    /*
     * Week one is buy-back-able, so losing it only ends the season for players
     * who did not buy back. That cannot be inferred from the picks: an
     * eliminated player can keep submitting them.
     */
    public function testAWeekOneLossEliminatesWithoutABuyback(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears']],
            $this->checker(['1:Bears' => Rules::PICK_INCORRECT])
        );

        $this->assertSame(Standings::OUT, $standings['joeg']['status']);
    }

    public function testABuybackSurvivesAWeekOneLoss(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears', 2 => 'Giants']],
            $this->checker([
                '1:Bears' => Rules::PICK_INCORRECT,
                '2:Giants' => Rules::PICK_CORRECT,
            ]),
            ['joeg']
        );

        $this->assertSame(Standings::IN, $standings['joeg']['status']);
    }

    /* Buying back covers week one only, not the rest of the season. */
    public function testABuybackDoesNotForgiveLaterWeeks(): void
    {
        $standings = Standings::build(
            ['joeg' => [1 => 'Bears', 2 => 'Giants']],
            $this->checker([
                '1:Bears' => Rules::PICK_INCORRECT,
                '2:Giants' => Rules::PICK_INCORRECT,
            ]),
            ['joeg']
        );

        $this->assertSame(Standings::OUT, $standings['joeg']['status']);
        $this->assertSame(2, $standings['joeg']['outWeek']);
    }

    public function testBuybacksMatchRegardlessOfCase(): void
    {
        $standings = Standings::build(
            ['JoeG' => [1 => 'Bears']],
            $this->checker(['1:Bears' => Rules::PICK_INCORRECT]),
            ['joeg']
        );

        $this->assertSame(Standings::IN, $standings['JoeG']['status']);
    }

    /* Registered players who never picked have not lost anything yet. */
    public function testRegisteredPlayersWithNoPicksAreIncludedAndStillIn(): void
    {
        $standings = Standings::build([], $this->checker([]), [], ['newcomer']);

        $this->assertArrayHasKey('newcomer', $standings);
        $this->assertSame(Standings::IN, $standings['newcomer']['status']);
    }

    public function testCountsWhoIsLeft(): void
    {
        $standings = Standings::build(
            [
                'alive1' => [1 => 'Bears'],
                'alive2' => [1 => 'Jets'],
                'goner' => [1 => 'Giants'],
            ],
            $this->checker([
                '1:Bears' => Rules::PICK_CORRECT,
                '1:Jets' => Rules::PICK_CORRECT,
                '1:Giants' => Rules::PICK_INCORRECT,
            ])
        );

        $this->assertSame(2, Standings::stillIn($standings));
    }

    /*
     * A tie eliminates, and Rules already reports one as an incorrect pick --
     * so standings need no special case, but it is worth pinning down.
     */
    public function testATiedPickEliminatesLikeAnyOtherFailure(): void
    {
        $standings = Standings::build(
            ['joeg' => [3 => 'Bears']],
            $this->checker(['3:Bears' => Rules::PICK_INCORRECT])
        );

        $this->assertSame(Standings::OUT, $standings['joeg']['status']);
        $this->assertSame(3, $standings['joeg']['outWeek']);
    }
}
