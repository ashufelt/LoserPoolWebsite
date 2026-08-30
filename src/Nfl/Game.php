<?php

namespace LoserPool\Nfl;

use DateTimeImmutable;

/*
 * A single NFL game, normalised out of an ESPN scoreboard payload.
 *
 * Kickoff is always stored in the pool's timezone, because every rule that
 * depends on it ("no Wednesday/Thursday/Friday teams") is a question about
 * the local day of the week, not the UTC instant.
 */
final class Game
{
    /** @var string[] */
    private array $teams;
    private DateTimeImmutable $kickoff;
    private bool $completed;
    private ?string $winner;

    /** @param string[] $teams */
    public function __construct(array $teams, DateTimeImmutable $kickoff, bool $completed, ?string $winner)
    {
        $this->teams = $teams;
        $this->kickoff = $kickoff;
        $this->completed = $completed;
        $this->winner = $winner;
    }

    /** @return string[] */
    public function teams(): array
    {
        return $this->teams;
    }

    public function kickoff(): DateTimeImmutable
    {
        return $this->kickoff;
    }

    /* Three-letter day of week in the pool timezone: "Wed", "Sun", ... */
    public function kickoffDay(): string
    {
        return $this->kickoff->format('D');
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function winner(): ?string
    {
        return $this->winner;
    }

    /*
     * A finished game with no winner is a tie. Ties are rare but real, and
     * ESPN reports them as `winner: false` on both competitors -- which would
     * otherwise score as "this team won", i.e. a losing pick.
     */
    public function isTie(): bool
    {
        return $this->completed && $this->winner === null;
    }

    public function involves(string $team): bool
    {
        return in_array($team, $this->teams, true);
    }
}
