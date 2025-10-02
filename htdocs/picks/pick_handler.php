<?php

namespace PickHandling;

include_once "../SqlAccess/SqlAccessController.php";
include_once "../data/week_manager.php";

use SqlAccess\SqlAccessController;

function ph_add_pick(string $userin, string $teamin, string $pinin): string
{
    $controller = new SqlAccessController();
    $user = htmlspecialchars($userin);
    $week = get_current_week();
    $team = htmlspecialchars($teamin);
    $pickpin = intval($pinin);
    if ($controller->user_exists($user) == false) {
        return "<h4>User does not exist</h4>";
    }
    $correctpin = $controller->get_user_pin($user);
    $week_number = intval($week);
    $users_picks = $controller->get_user_all_picks($user);
    $create_ecode = 0;
    if ($pickpin != $correctpin) {
        return "<h4>Username/PIN combo is not valid</h4>";
    } else if (in_array($team, $users_picks, true)) {
        return "<h4>Cannot repeat a choice</h4>";
    } else if (is_sunday_or_monday() && intval(date('z')) > 245) {
       return "<h4>Can't make a pick on Sunday or Monday</h4>";
    } else if (0 != ($create_ecode = $controller->add_pick($user, $team, $week_number))) {
        if ($create_ecode == 2) {
            return "<h4>Invalid username</h4>";
        } else if ($create_ecode == 1) {
            return "<h4>Database error. Try viewing your pick or submitting again.</h4>
                  <p>If you aren't able to verify your pick with 'View my picks', 
                    email adam.shufelt.official@gmail.com to ensure that your 
                    pick is received. I do not expect this message to ever appear.</p>";
        }
    } else {
        return "<h3>Pick added successfully</h3><br>";
    }
}

function ph_get_picks_html_table(): string
{
    $controller = new SqlAccessController();
    $show_weeks_count = 8;
    $hide_picks = !is_sunday_or_monday() || intval(date('z')) < 245;
    $current_week = get_current_week();
    if ($current_week <= $show_weeks_count) {
        $start_week = 1;
        $end_week = $show_weeks_count;
    } else {
        $start_week = $current_week - $show_weeks_count + 1;
        $end_week = $current_week;
    }

    $picks_html_table = "
        <div id='picks_table'>
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
            $result_bg_styling = "";
            if (array_key_exists($i, $users_picks)) {
                $pick = $users_picks[$i];
                if ($hide_picks && ($i == $current_week)) {
                    $pick = "Submitted";
                } else {
                    $team_result = check_loser($i, $pick);
                    if ($team_result == -1) {
                        $result_bg_styling = "style='background-color:#d63131'";
                    } else if ($team_result == 1) {
                        $result_bg_styling = "style='background-color:#3e9c3e'";
                    }
                }
            }
            $picks_html_table .= "<td class='pick_table pick_team'" .  $result_bg_styling . ">" . $pick . "</td>";
        }
        $picks_html_table .= "</tr>";
    }

    $picks_html_table .= "</table></div>";
    return $picks_html_table;
}

function ph_get_user_picks_html(string $user, string $pin)
{
    $controller = new SqlAccessController();
    $user = htmlspecialchars($user);
    $pin_num = intval($pin);
    if ($pin_num != $controller->get_user_pin($user)) {
        return "<h4>Username/PIN combo is not valid</h4>";
    }

    $picks_html_table = "<div class=users_picks>
                         <table class='users_picks'>
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
    $picks_html_table .= "</table>
                            <button hx-get='/picks/hide.php' hx-target='#one_set_of_picks' 
                                    hx-swap='innerHTML' class='hidepicks' type='submit' value='Hide picks'>
                                Hide picks
                            </button>           
                            </div>";
    return $picks_html_table;
}

function ph_get_user_picks_list(string $user)
{
    $controller = new SqlAccessController();
    $user = htmlspecialchars($user);
    return $controller->get_user_all_picks($user);
}

function ph_clear_picks_table(): bool
{
    $controller = new SqlAccessController();
    if (0 == $controller->clear_pick_table()) {
        return true;
    } else {
        return false;
    }
}
