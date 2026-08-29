<?php

namespace TeamHandler;

include_once __DIR__ . "/../week_manager.php";
include_once __DIR__ . "/pick_handler.php";

use LoserPool\Nfl\Teams;
use function PickHandling\ph_get_user_picks_list;

/* Each option carries its crest path so the page needs no second team map. */
function ph_team_option(string $team): string
{
    $logo = Teams::logoPath($team);
    $attribute = $logo === null ? "" : " data-logo='" . $logo . "'";
    return "<option" . $attribute . ">" . $team . "</option>";
}

function get_team_options_html($user = ""): string
{
    $options_list = "";

    $options_list .= "<select id='teams' name='team'>";
    $users_picks = [];

    if ($user != "") {
        $users_picks = ph_get_user_picks_list($user);
    }

    foreach (Teams::all() as $team) {
        if (!in_array($team, get_INELIGIBLE_teams(get_current_week())) && !in_array($team, $users_picks)) {
            $options_list .= ph_team_option($team);
        } else if (in_array($team, $users_picks) && (array_key_exists(get_current_week(), $users_picks))) {
            if ($team == $users_picks[get_current_week()]) {
                $options_list .= ph_team_option($team);
            }
        }
    }
    $options_list .= "</select>";
    return $options_list;
}
