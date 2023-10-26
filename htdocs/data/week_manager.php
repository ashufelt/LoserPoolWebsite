<?php

//Also include bye week teams
const TNF = [
    ["Detroit Lions", "Kansas City Chiefs"],  // 1
    ["Minnesota Vikings", "Philadelphia Eagles"], // 2
    ["New York Giants", "San Francisco 49ers"],   // 3
    ["Detroit Lions", "Green Bay Packers"],       // 4
    [
        "Chicago Bears", "Washington Commanders",
        "Cleveland Browns", "Los Angeles Chargers",
        "Seattle Seahawks", "Tampa Bay Buccaneers"
    ],   // 5
    [
        "Denver Broncos", "Kansas City Chiefs",
        "Green Bay Packers", "Pittsburgh Steelers"
    ],     // 6
    [
        "Jacksonville Jaguars", "New Orleans Saints",
        "Carolina Panthers", "Cincinnati Bengals",
        "Dallas Cowboys", "Houston Texans",
        "New York Jets", "Tennessee Titans"
    ], // 7
    ["Tampa Bay Buccaneers", "Buffalo Bills"],    // 8
    [
        "Tennessee Titans", "Pittsburgh Steelers",
        "Denver Broncos", "Detroit Lions",
        "Jacksonville Jaguars", "San Francisco 49ers"
    ],  // 9
    [
        "Carolina Panthers", "Chicago Bears",
        "Kansas City Chiefs", "Los Angeles Rams",
        "Miami Dolphins", "Philadelphia Eagles"
    ],       // 10
    [
        "Cincinnati Bengals", "Baltimore Ravens",
        "Atlanta Falcons", "Indianapolis Colts",
        "New England Patriots", "New Orleans Saints"
    ],   // 11
    [
        "Green Bay Packers", "Detroit Lions",
        "Washington Commanders", "Dallas Cowboys",
        "San Francisco 49ers", "Seattle Seahawks",
        "Miami Dolphins", "New York Jets"
    ],       // 12
    [
        "Seattle Seahawks", "Dallas Cowboys",
        "Baltimore Ravens", "Buffalo Bills",
        "Chicago Bears", "Las Vegas Raiders",
        "Minnesota Vikings", "New York Giants"
    ],       // 13
    [
        "New England Patriots", "Pittsburgh Steelers",
        "Arizona Cardinals", "Washington Commanders"
    ], // 14
    ["Los Angeles Chargers", "Las Vegas Raiders"], // 15
    ["New Orleans Saints", "Los Angeles Rams"],    // 16
    ["New York Jets", "Cleveland Browns"]          // 17
];

const losers = [
    [
        "Cincinnati Bengals",
        "Houston Texans",
        "Minnesota Vikings",
        "Carolina Panthers",
        "Arizona Cardinals",
        "Indianapolis Colts",
        "Pittsburgh Steelers",
        "Tennessee Titans",
        "Denver Broncos",
        "New England Patriots",
        "Seattle Seahawks",
        "Los Angeles Chargers",
        "Chicago Bears",
        "Buffalo Bills",
        "New York Giants",
    ], //week 1
    [
        "Minnesota Vikings",
        "Cincinnati Bengals",
        "Detroit Lions",
        "Houston Texans",
        "Chicago Bears",
        "Jacksonville Jaguars",
        "Green Bay Packers",
        "Las Vegas Raiders",
        "Los Angeles Chargers",
        "Los Angeles Rams",
        "Arizona Cardinals",
        "New York Jets",
        "Denver Broncos",
        "New England Patriots",
        "Carolina Panthers",
        "Cleveland Browns",
    ], //week 2
    [
        "Atlanta Falcons",
        "Minnesota Vikings",
        "New Orleans Saints",
        "Jacksonville Jaguars",
        "Denver Broncos",
        "Tennessee Titans",
        "Washington Commanders",
        "Baltimore Ravens",
        "New York Jets",
        "Carolina Panthers",
        "Chicago Bears",
        "Dallas Cowboys",
    ], //week 3
    [
        "Arizona Cardinals",
        "Washington Commanders",
        "New York Jets",
        "Indianapolis Colts",
        "Carolina Panthers",
        "Las Vegas Raiders",
    ], //week 4
    [
        "New York Giants",
        "Carolina Panthers",
        "Dallas Cowboys",
        "Arizona Cardinals",
    ], //week 5
    [
        "Carolina Panthers",
        "New York Giants",
        "Indianapolis Colts",
        "Arizona Cardinals"
    ], //week 6
    [
        "Los Angeles Chargers",
        "Arizona Cardinals",
    ], //week 7
    [], //week 8
    [], //week 9
    [], //week 10
    [], //week 11
    [], //week 12
    [], //week 13
    [], //week 14
    [], //week 15
    [], //week 16
    [], //week 17
    [], //week 18
];

const winners = [
    [
        "Cleveland Browns",
        "Baltimore Ravens",
        "Tampa Bay Buccaneers",
        "Atlanta Falcons",
        "Washington Commanders",
        "Jacksonville Jaguars",
        "San Francisco 49ers",
        "New Orleans Saints",
        "Las Vegas Raiders",
        "Philadelphia Eagles",
        "Los Angeles Rams",
        "Miami Dolphins",
        "Green Bay Packers",
        "New York Jets",
        "Dallas Cowboys",
    ], //week 1
    [
        "Philadelphia Eagles",
        "Baltimore Ravens",
        "Seattle Seahawks",
        "Indianapolis Colts",
        "Tampa Bay Buccaneers",
        "Kansas City Chiefs",
        "Atlanta Falcons",
        "Buffalo Bills",
        "Tennessee Titans",
        "San Francisco 49ers",
        "New York Giants",
        "Dallas Cowboys",
        "Washington Commanders",
        "Miami Dolphins",
        "New Orleans Saints",
        "Pittsburgh Steelers",
    ], //week 2
    [
        "Detroit Lions",
        "Los Angeles Chargers",
        "Green Bay Packers",
        "Houston Texans",
        "Miami Dolphins",
        "Cleveland Browns",
        "Buffalo Bills",
        "Indianapolis Colts",
        "New England Patriots",
        "Seattle Seahawks",
        "Kansas City Chiefs",
        "Arizona Cardinals",
        "Cincinnati Bengals",
    ], //week 3
    [], //week 4
    [
        "Pittsburgh Steelers",
        "Jacksonville Jaguars",
    ], //week 5
    [
        "Cleveland Browns",
        "New York Jets"
    ], //week 6
    [
        "New England Patriots",
        "Minnesota Vikings",
        "Pittsburgh Steelers"
    ], //week 7
    [], //week 8
    [], //week 9
    [], //week 10
    [], //week 11
    [], //week 12
    [], //week 13
    [], //week 14
    [], //week 15
    [], //week 16
    [], //week 17
    [], //week 18
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
