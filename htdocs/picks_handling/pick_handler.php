<?php

namespace PickHandling;

include_once "db_interface/SqlAccessController.php";

use SqlAccess\SqlAccessController;

function ph_add_pick(SqlAccessController $controller, array $params_list): bool
{
    $user = htmlspecialchars($params_list['userpick']);
    $week = htmlspecialchars($params_list['week']);
    $team = htmlspecialchars($params_list['team']);
    $week_number = intval($week);
    $users_picks = $controller->get_user_all_picks($user);
    if (in_array($team, $users_picks, true)) {
        return false;
    } else if (is_sunday_or_monday()) {
        return false;
    } else if (0 != $controller->add_pick($user, $team, $week_number)) {
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
        $start_week = $current_week - $show_weeks_count;
        $end_week = $current_week;
    }

    $picks_html_table = "
            <table class='pick_table'>
                <tr class='pick_table'>
                    <th class='pickcolumn1 pick_table'>Username</th>";
    for ($i = 0; $i < $end_week; $i++) {
        $picks_html_table .= "<th class='pickHeader pick_table'>Week " . ($i + 1) . "</th>";
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

function ph_clear_picks_table(SqlAccessController $controller): bool
{
    if (0 == $controller->clear_pick_table()) {
        return true;
    } else {
        return false;
    }
}
