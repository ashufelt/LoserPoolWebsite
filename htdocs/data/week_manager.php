<?php

/* 
 * 2025
 * September 1st = 243
 * Week 1: <= 249
 * 251 <= Week 2 <= 257
 * 258 <= Week 3 <= 264
 */


function get_current_week(): int
{
    date_default_timezone_set("America/Chicago");
    $day_of_year = intval(date('z'));
    $year = intval(date('Y'));
    if ($year == 2026) {
        return 18;
    }
    if ($day_of_year <= 250) {
        return 1;
    } else {
        return intdiv(($day_of_year - 237), 7);
    }
}

function is_sunday_or_monday(): bool
{
    date_default_timezone_set("America/Chicago");
    $day = date('D');
    return (($day == "Mon") || ($day == "Sun"));
}

function get_INELIGIBLE_teams($week): array
{
    return INELIGIBLE_2025[$week - 1];
}

// Returns -1 if a team was an incorrect pick, 1 if correct, 0 if undetermined
function check_loser($week, $team): int
{
    if (in_array($team, LOSERS_2025 [$week - 1])) {
        return 1;
    } else if (in_array($team, WINNERS_2025 [$week - 1])) {
        return -1;
    } else {
        return 0;
    }
}

const LOSERS_2025 = [
    [
        "Atlanta Falcons",
        "New York Jets",
        "Miami Dolphins",
        "New York Giants",
        "New Orleans Saints",
        "Cleveland Browns",
        "New England Patriots",
        "Seattle Seahawks",
        "Tennessee Titans",
        "Detroit Lions",
        "Houston Texans",
        "Carolina Panthers",
        "Baltimore Ravens",
        "Chicago Bears"
    ], //week 1
    [
        "Tennessee Titans",
        "Pittsburgh Steelers",
        "New York Jets",
        "Chicago Bears",
        "New York Giants",
        "Cleveland Browns",
        "New Orleans Saints",
        "Miami Dolphins",
        "Jacksonville Jaguars",
        "Carolina Panthers",
        "Denver Broncos",
        "Kansas City Chiefs",
        "Minnesota Vikings",
        "Las Vegas Raiders"
    ], //week 2
    [
        "Tennessee Titans",
        "Las Vegas Raiders",
        "Los Angeles Rams",
        "Atlanta Falcons",
        "New England Patriots",
        "Green Bay Packers",
        "New York Jets",
        "Houston Texans",
        "Denver Broncos",
        "New Orleans Saints",
        "Dallas Cowboys",
        "Arizona Cardinals",
        "New York Giants"
    ], //week 3
    [
        "New Orleans Saints",
        "Carolina Panthers",
        "Cleveland Browns",
        "Tampa Bay Buccaneers",
        "Tennessee Titans",
        "Indianapolis Colts",
        "Cincinnati Bengals"
    ], //week 4
    [], //week 5
    [], //week 6
    [], //week 7
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

const WINNERS_2025 = [
    [
        "Tampa Bay Buccaneers",
        "Pittsburgh Steelers",
        "Indianapolis Colts",
        "Washington Commanders",
        "Arizona Cardinals",
        "Cincinnati Bengals",
        "Las Vegas Raiders",
        "San Francisco 49ers",
        "Denver Broncos",
        "Green Bay Packers",
        "Los Angeles Rams",
        "Jacksonville Jaguars",
        "Buffalo Bills",
        "Minnesota Vikings"
    ], //week 1
    [
        "Buffalo Bills",
        "Cincinnati Bengals",
        "Detroit Lions",
        "New England Patriots"
    ], //week 2
    [
        "Indianapolis Colts",
        "Washington Commanders",
        "Philadelphia Eagles",
        "Carolina Panthers",
        "Pittsburgh Steelers",
        "Cleveland Browns",
        "Tampa Bay Buccaneers",
        "Jacksonville Jaguars",
        "Chicago Bears",
        "San Francisco 49ers",
        "Detroit Lions"
    ], //week 3
    [
        "Los Angeles Chargers",
        "New York Giants",
        "Dallas Cowboys",
        "Atlanta Falcons"
    ], //week 4
    [], //week 5
    [], //week 6
    [], //week 7
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

const INELIGIBLE_2025 = [
    [
        "Philadelphia Eagles", "Dallas Cowboys",
        "Kansas City Chiefs", "Los Angeles Chargers"
    ], //week 1
    ["Green Bay Packers", "Washington Commanders"], //week 2
    ["Miami Dolphins", "Buffalo Bills"], //week 3
    ["Seattle Seahawks", "Arizona Cardinals"], //week 4
    [
        "San Francisco 49ers", "Los Angeles Rams",
        "Atlanta Falcons", "Green Bay Packers",
        "Chicago Bears", "Pittsburgh Steelers"
    ], //week 5
    [
        "Philadelphia Eagles", "New York Giants",
        "Houston Texans", "Minnesota Vikings"
    ], //week 6
    [
        "Pittsburgh Steelers", "Cincinnati Bengals",
        "Baltimore Ravens", "Buffalo Bills"
    ], //week 7
    [
        "Minnesota Vikings", "Los Angeles Chargers",
        "Arizona Cardinals", "Detroit Lions",
        "Jacksonville Jaguars", "Las Vegas Raiders",
        "Los Angeles Rams", "Seattle Seahawks"
    ], //week 8
    [
        "Baltimore Ravens", "Miami Dolphins",
        "Cleveland Browns", "New York Jets",
        "Philadelphia Eagles", "Tampa Bay Buccaneers"
    ], //week 9
    [
        "Las Vegas Raiders", "Denver Broncos",
        "Cincinnati Bengals", "Dallas Cowboys",
        "Kansas City Chiefs", "Tennessee Titans"
    ], //week 10
    [
        "New York Jets", "New England Patriots",
        "Indianapolis Colts", "New Orleans Saints"
    ], //week 11
    [
        "Buffalo Bills", "Houston Texans",
        "Denver Broncos", "Los Angeles Chargers",
        "Miami Dolphins", "Washington Commanders"
    ], //week 12
    [
        "Green Bay Packers", "Detroit Lions",
        "Kansas City Chiefs", "Dallas Cowboys",
        "Cincinnati Bengals", "Baltimore Ravens"
    ], //week 13
    [
        "Dallas Cowboys", "Detroit Lions",
        "Carolina Panthers", "New England Patriots",
        "New York Giants", "San Francisco 49ers"
    ], //week 14
    ["Atlanta Falcons", "Tampa Bay Buccaneers"], //week 15
    ["Los Angeles Rams", "Seattle Seahawks"], //week 16
    [], //week 17
    [], //week 18
];

const LOSERS_2024 = [
    [
        "Atlanta Falcons",
        "New York Giants",
        "Jacksonville Jaguars",
        "Cincinnati Bengals",
        "Arizona Cardinals",
        "Indianapolis Colts",
        "Tennessee Titans",
        "Carolina Panthers",
        "Las Vegas Raiders",
        "Denver Broncos",
        "Washington Commanders",
        "Cleveland Browns",
        "Los Angeles Rams",
        "New York Jets"
    ], //week 1
    [
        "Indianapolis Colts",
        "Dallas Cowboys",
        "Baltimore Ravens",
        "New York Giants",
        "Carolina Panthers",
        "San Francisco 49ers",
        "Jacksonville Jaguars",
        "New England Patriots",
        "Tennessee Titans",
        "Detroit Lions",
        "Los Angeles Rams",
        "Denver Broncos",
        "Cincinnati Bengals",
        "Chicago Bears",
        "Philadelphia Eagles"
    ], //week 2
    [
        "Miami Dolphins",
        "Atlanta Falcons"
    ], //week 3
    [
        "New England Patriots"
    ], //week 4
    [], //week 5
    [], //week 6
    [], //week 7
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

const WINNERS_2024 = [
    [
        "Pittsburgh Steelers",
        "Minnesota Vikings",
        "Miami Dolphins",
        "New England Patriots",
        "Buffalo Bills",
        "Houston Texas",
        "Chicago Bears",
        "New Orleans Saints",
        "Los Angeles Chargers",
        "Seattle Seahawks",
        "Tampa Bay Buccaneers",
        "Dallas Cowboys",
        "Detroit Lions",
        "San Francisco 49ers"
    ], //week 1
    [
        "Green Bay Packers",
        "New Orleans Saints",
        "Las Vegas Raiders",
        "Washington Commanders",
        "Los Angeles Chargers",
        "Minnesota Vikings",
        "Cleveland Browns",
        "Seattle Seahawks",
        "New York Jets",
        "Tampa Bay Buccaneers",
        "Arizona Cardinals",
        "Pittsburgh Steelers",
        "Kansas City Chiefs",
        "Houston Texans",
        "Atlanta Falcons"
    ], //week 2
    [
        "Denver Broncos",
        "Los Angeles Rams",
        "Carolina Panthers",
        "Minnesota Vikings",
        "New York Giants",
        "Indianapolis Colts",
        "Washington Commanders"
    ], //week 3
    [
        "Indianapolis Colts"
    ], //week 4
    [
        "New York Giants",
        "Arizona Cardinals"
    ], //week 5
    [], //week 6
    [], //week 7
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

const INELIGIBLE_2024 = [
    [
        "Baltimore Ravens", "Kansas City Chiefs",
        "Green Bay Packers", "Philadelphia Eagles"
    ], //week 1
    [
        "Buffalo Bills", "Miami Dolphins"
    ], //week 2
    [
        "New England Patriots", "New York Jets"
    ], //week 3
    [
        "Dallas Cowboys", "New York Giants"
    ], //week 4
    [
        "Tampa Bay Buccaneers", "Atlanta Falcons",
        "Tennessee Titans", "Los Angeles Chargers",
        "Philadelphia Eagles", "Detroit Lions"
    ], //week 5
    [
        "San Francisco 49ers", "Seattle Seahawks",
        "Miami Dolphins", "Kansas City Chiefs",
        "Minnesota Vikings", "Los Angeles Rams"
    ], //week 6
    [
        "Denver Broncos", "New Orleans Saints",
        "Dallas Cowboys", "Chicago Bears"
    ], //week 7
    [
        "Minnesota Vikings", "Los Angeles Rams"
    ], //week 8
    [
        "Houston Texans", "New York Jets",
        "Pittsburgh Steelers", "San Francisco 49ers"
    ], //week 9
    [
        "Cincinnati Bengals", "Baltimore Ravens",
        "Cleveland Browns", "Las Vegas Raiders",
        "Seattle Seahawks", "Green Bay Packers"
    ], //week 10
    [
        "Washington Commanders", "Philadelphia Eagles",
        "New York Giants", "Arizona Cardinals",
        "Tampa Bay Buccaneers", "Carolina Panthers"
    ], //week 11
    [
        "Pittsburgh Steelers", "Cleveland Browns",
        "Buffalo Bills", "New York Jets",
        "Cincinnati Bengals", "Atlanta Falcons",
        "New Orleans Saints", "Jacksonville Jaguars"
    ], //week 12
    [
        "Chicago Bears", "Detroit Lions",
        "New York Giants", "Dallas Cowboys",
        "Miami Dolphins", "Green Bay Packers",
        "Las Vegas Raiders", "Kansas City Chiefs"
    ], //week 13
    [
        "Green Bay Packers", "Detroit Lions",
        "Indianapolis Colts", "New England Patriots",
        "Denver Broncos", "Washington Commanders",
        "Baltimore Ravens", "Houston Texans"
    ], //week 14
    [
        "Los Angeles Rams", "San Francisco 49ers"
    ], //week 15
    [
        "Cleveland Browns", "Cincinnati Bengals"
    ], //week 16
    [
        "Kansas City Chiefs", "Pittsburgh Steelers",
        "Baltimore Ravens", "Houston Texans",
        "Seattle Seahawks", "Chicago Bears"
    ], //week 17
    [], //week 18
];

const losers_2023 = [
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
    [
        "Arizona Cardinals",
        "Las Vegas Raiders",
        "Los Angeles Rams"
    ], //week 8
    [
        "Seattle Seahawks"
    ], //week 9
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

const winners_2023 = [
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
    [
        "Minnesota Vikings",
        "Green Bay Packers"
    ], //week 9
    [
        "Houston Texans",
        "Denver Broncos"
    ], //week 10
    [], //week 11
    [], //week 12
    [], //week 13
    [], //week 14
    [], //week 15
    [], //week 16
    [], //week 17
    [], //week 18
];

const INELIGIBLE_2023 = [
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