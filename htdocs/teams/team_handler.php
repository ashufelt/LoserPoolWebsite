<?php

namespace TeamHandler;

include_once "../data/week_manager.php";
include_once "../picks/pick_handler.php";

use function PickHandling\ph_get_user_picks_list;

const TEAMS = [
    "Arizona Cardinals",
    "Atlanta Falcons",
    "Baltimore Ravens",
    "Buffalo Bills",
    "Carolina Panthers",
    "Chicago Bears",
    "Cincinnati Bengals",
    "Cleveland Browns",
    "Dallas Cowboys",
    "Denver Broncos",
    "Detroit Lions",
    "Green Bay Packers",
    "Houston Texans",
    "Indianapolis Colts",
    "Jacksonville Jaguars",
    "Kansas City Chiefs",
    "Las Vegas Raiders",
    "Los Angeles Chargers",
    "Los Angeles Rams",
    "Miami Dolphins",
    "Minnesota Vikings",
    "New England Patriots",
    "New Orleans Saints",
    "New York Giants",
    "New York Jets",
    "Philadelpia Eagles",
    "Pittsburgh Steelers",
    "San Francisco 49ers",
    "Seattle Seahawks",
    "Tampa Bay Buccaneers",
    "Tennessee Titans",
    "Washington Commanders"
];

function get_team_options_html($user = ""): string
{
    $options_list = "";

    $options_list .= "<select id='teams' name='team'>";
    $users_picks = [];

    if ($user != "") {
        $users_picks = ph_get_user_picks_list($user);
    }

    foreach (TEAMS as $team) {
        if (!in_array($team, get_TNF_teams(get_current_week())) && !in_array($team, $users_picks)) {
            $options_list .= "<option>" . $team . "</option>";
        } else if (in_array($team, $users_picks) && ($team == $users_picks[get_current_week()])) {
            $options_list .= "<option>" . $team . "</option>";
        }
    }
    $options_list .= "</select>";
    return $options_list;
}
