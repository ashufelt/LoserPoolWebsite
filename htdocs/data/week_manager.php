<?php

const TNF = [
    ["Detroit Lions", "Kansas City Chiefs"],  // 1
    ["Minnesota Vikings", "Philadelphia Eagles"], // 2
    ["New York Giants", "San Francisco 49ers"],   // 3
    ["Detroit Lions", "Green Bay Packers"],       // 4
    ["Chicago Bears", "Washington Commanders"],   // 5
    ["Denver Broncos", "Kansas City Chiefs"],     // 6
    ["Jacksonville Jaguars", "New Orleans Saints"], // 7
    ["Tampa Bay Buccaneers", "Buffalo Bills"],    // 8
    ["Tennessee Titans", "Pittsburgh Steelers"],  // 9
    ["Carolina Panthers", "Chicago Bears"],       // 10
    ["Cincinnati Bengals", "Baltimore Ravens"],   // 11
    [
        "Green Bay Packers", "Detroit Lions",
        "Washington Commanders", "Dallas Cowboys",
        "San Francisco 49ers", "Seattle Seahawks",
        "Miami Dolphins", "New York Jets"
    ],       // 12
    ["Seattle Seahawks", "Dallas Cowboys"],       // 13
    ["New England Patriots", "Pittsburgh Steelers"], // 14
    ["Los Angeles Chargers", "Las Vegas Raiders"], // 15
    ["New Orleans Saints", "Los Angeles Rams"],    // 16
    ["New York Jets", "Cleveland Browns"]          // 17
];

const losers = [
    [
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
    ],
    [],
];

const winners = [
    [
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
    ],
    [],
];

/*
 * September 1st = 243
 * Week 1: <= 253
 * 254 <= Week 2 <= 260
 * 261 <= Week 3 <= 267
 */


function get_current_week(): int
{
    date_default_timezone_set("America/Chicago");
    $day_of_year = intval(date('z'));
    $year = intval(date('Y'));
    if ($year == 2024) {
        return 18;
    }
    if ($day_of_year <= 253) {
        return 1;
    } else {
        return intdiv(($day_of_year - 240), 7);
    }
}

function is_sunday_or_monday(): bool
{
    date_default_timezone_set("America/Chicago");
    $day = date('D');
    return (($day == "Mon") || ($day == "Sun"));
}

function get_TNF_teams($week): array
{
    return TNF[$week - 1];
}

// Returns -1 if a team was an incorrect pick, 1 if correct, 0 if undetermined
function check_loser($week, $team): int
{
    if (in_array($team, losers[$week - 1])) {
        return 1;
    } else if (in_array($team, winners[$week - 1])) {
        return -1;
    } else {
        return 0;
    }
}
