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
