<?php

namespace TeamHandler;

include_once __DIR__ . "/../week_manager.php";
include_once __DIR__ . "/pick_handler.php";

use LoserPool\Nfl\Teams;
use function PickHandling\ph_get_user_picks_list;

/*
 * Each option carries its own crest and club colour, so the enhanced picker
 * needs no second copy of the team map on the client.
 */
function ph_team_option(string $team, ?string $unavailableBecause = null): string
{
    $attributes = "";

    $logo = Teams::logoPath($team);
    if ($logo !== null) {
        $attributes .= " data-logo='" . $logo . "'";
    }

    $color = Teams::color($team);
    if ($color !== null) {
        $attributes .= " data-color='" . $color . "'";
    }

    if ($unavailableBecause !== null) {
        /* disabled keeps it unselectable in the native control too, not just
           in the enhanced picker, so the rule holds without JavaScript. */
        $attributes .= " disabled data-reason='" . htmlspecialchars($unavailableBecause) . "'";
    }

    return "<option" . $attributes . ">" . $team . "</option>";
}

function get_team_options_html($user = ""): string
{
    $options_list = "";

    $options_list .= "<select id='teams' name='team'>";
    $users_picks = [];

    if ($user != "") {
        $users_picks = ph_get_user_picks_list($user);
    }

    /*
     * Every team is listed. Unavailable ones are shown greyed out and
     * unselectable, with the reason, rather than omitted: a team that is simply
     * missing is indistinguishable from one that never existed, and a player
     * hunting for it cannot tell whether it is on a bye, playing before the
     * deadline, or one they already used.
     */
    $week = get_current_week();
    $reasons = get_INELIGIBLE_reasons($week);
    $this_weeks_pick = $users_picks[$week] ?? null;

    foreach (Teams::all() as $team) {
        /* This week's own pick stays selectable, so the current choice shows. */
        if ($team === $this_weeks_pick) {
            $options_list .= ph_team_option($team);
            continue;
        }

        $used_in = array_search($team, $users_picks, true);
        if ($used_in !== false) {
            $options_list .= ph_team_option($team, 'Used in week ' . $used_in);
            continue;
        }

        $options_list .= ph_team_option($team, $reasons[$team] ?? null);
    }
    $options_list .= "</select>";
    return $options_list;
}
