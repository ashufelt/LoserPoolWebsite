<?php

namespace PickHandling;

include_once "db_interface/SqlAccessController.php";

use SqlAccess\SqlAccessController;

function ph_add_pick(SqlAccessController $controller, string $userin, string $weekin, string $teamin, string $pinin): bool
{
    $user = htmlspecialchars($userin);
    $week = htmlspecialchars($weekin);
    $team = htmlspecialchars($teamin);
    $pickpin = intval($pinin);
    $correctpin = $controller->get_user_pin($user);
    $week_number = intval($week);
    $users_picks = $controller->get_user_all_picks($user);
    $create_ecode = 0;
    echo print_r($user, true) . "\n";
    echo print_r($week, true) . "\n";
    echo print_r($team, true) . "\n";
    echo print_r($pickpin, true) . "\n";
    echo print_r($correctpin, true) . "\n";
    if ($pickpin != $correctpin) {
        echo "<h4>Username/PIN combo is not valid</h4>";
        return false;
    } else if (in_array($team, $users_picks, true)) {
        echo "<h4>Cannot repeat a choice</h4>";
        return false;
    } else if (is_sunday_or_monday()) {
        echo "<h4>Can't make a pick on Sunday or Monday</h4>";
        return false;
    } else if (0 != ($create_ecode = $controller->add_pick($user, $team, $week_number))) {
        if ($create_ecode == 2) {
            echo "<h4>Invalid username</h4>";
            return false;
        } else if ($create_ecode == 1) {
            echo "<h4>Database error. Try viewing your pick or submitting again.</h4>
                  <p>If you aren't able to verify your pick with 'View my picks', 
                    email adam.shufelt.official@gmail.com to ensure that your 
                    pick is received. I do not expect this message to ever appear.</p>";
            return false;
        }
        return false;
    } else {
        return true;
    }
}

function ph_get_picks_html_table(SqlAccessController $controller): string
{
    $show_weeks_count = 8;
    $hide_picks = !is_sunday_or_monday();
    $current_week = get_current_week();
    if ($current_week <= $show_weeks_count) {
        $start_week = 1;
        $end_week = $show_weeks_count;
    } else {
        $start_week = $current_week - $show_weeks_count + 1;
        $end_week = $current_week;
    }

    $picks_html_table = "
            <table class='pick_table'>
                <tr class='pick_table'>
                    <th class='pickcolumn1 pick_table'>Username</th>";
    for ($i = $start_week; $i <= $end_week; $i++) {
        $picks_html_table .= "<th class='pickHeader pick_table'>Week " . ($i) . "</th>";
    }
    $picks_html_table .= "</tr>";

    $users = $controller->get_user_table();
    sort($users, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($users as $user) {
        $picks_html_table .= "<tr class='pick_table'><td class='pickcolumn1 pick_table'>" . $user . "</td>";
        $users_picks = $controller->get_user_all_picks($user);
        for ($i = $start_week; $i <= $end_week; $i++) {
            $pick = "";
            if (array_key_exists($i, $users_picks)) {
                $pick = $users_picks[$i];
                if ($hide_picks && ($i == $current_week)) {
                    $pick = "Submitted";
                }
            }
            $picks_html_table .= "<td class='pick_table pick_team'>" . $pick . "</td>";
        }
        $picks_html_table .= "</tr>";
    }

    $picks_html_table .= "</table>";
    return $picks_html_table;
}

function ph_get_user_picks_html(SqlAccessController $controller, string $user, string $pin)
{
    $user = htmlspecialchars($user);
    $pin_num = intval($pin);
    if ($pin_num != $controller->get_user_pin($user)) {
        return "<h4>Username/PIN combo is not valid</h4>";
    }

    $picks_html_table = "<table class='users_picks'>
                            <tr class='users_picks'>
                                <th class='users_picks'>Week</th>
                                <th class='users_picks'>Pick</th>";

    $users_picks = $controller->get_user_all_picks($user);
    for ($i = 1; $i <= get_current_week(); $i++) {
        $pick = "";
        if (array_key_exists($i, $users_picks)) {
            $pick = $users_picks[$i];
        }
        $picks_html_table .= "<tr class='users_picks'>
                                <td class='users_picks'>" . $i . "</td>
                                <td class='users_picks pick_team'>" . $pick . "</td></tr>";
    }
    $picks_html_table .= "</table><br><br>";
    return $picks_html_table;
}

function ph_clear_picks_table(SqlAccessController $controller): bool
{
    if (0 == $controller->clear_pick_table()) {
        return true;
    } else {
        return false;
    }
}
