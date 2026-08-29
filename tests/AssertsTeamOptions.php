<?php

namespace LoserPool\Tests;

/*
 * Assertions about the team dropdown.
 *
 * Unavailable teams are listed rather than omitted, so "is this team pickable"
 * is no longer answered by whether its name appears in the markup. These
 * assertions look at the option itself.
 */
trait AssertsTeamOptions
{
    private function optionFor(string $html, string $team): string
    {
        $pattern = '/<option[^>]*>' . preg_quote($team, '/') . '<\/option>/';
        preg_match($pattern, $html, $matches);

        $this->assertNotEmpty($matches, "no option found for $team");

        return $matches[0];
    }

    private function assertTeamUnavailable(string $html, string $team, ?string $reason = null): void
    {
        $option = $this->optionFor($html, $team);

        $this->assertStringContainsString('disabled', $option, "$team should not be pickable");

        if ($reason !== null) {
            $this->assertStringContainsString(
                "data-reason='" . $reason . "'",
                $option,
                "$team should explain why it is unavailable"
            );
        }
    }

    private function assertTeamSelectable(string $html, string $team): void
    {
        $option = $this->optionFor($html, $team);

        $this->assertStringNotContainsString('disabled', $option, "$team should be pickable");
    }
}
