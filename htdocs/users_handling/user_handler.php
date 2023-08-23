<?php

namespace UserHandling;

include_once "db_interface/SqlAccessController.php";

use SqlAccess\SqlAccessController;

function uh_add_user(SqlAccessController $controller, string $new_user, string $pin, string $repin): bool
{
    $new_user = htmlspecialchars($new_user);
    if ($pin != $repin) {
        return false;
    } else if (strlen($new_user) == 0) {
        return false;
    } else if (0 == $controller->add_user($new_user, intval($pin))) {
        return true;
    } else {
        return false;
    }
}

function uh_get_users_html_table(SqlAccessController $controller): string
{
    $users = $controller->get_user_table();
    $user_table = " <table class='user_table'] <tr> <th class='thcolumn1'>Username</th> </tr>";
    if (is_countable($users)) {
        foreach ($users as $user) {
            $addition = "<tr><td>" . $user . "</td></tr>";
            $user_table .= $addition;
        }
    }

    $user_table .= "</table>";
    return $user_table;
}

function uh_clear_users_table(SqlAccessController $controller): bool
{
    if (0 == $controller->clear_user_table()) {
        return true;
    } else {
        return false;
    }
}

function uh_get_user_option_list_html(SqlAccessController $controller): string
{
    $option_list = '';

    $users = $controller->get_user_table();
    sort($users, SORT_NATURAL | SORT_FLAG_CASE);
    if (is_countable($users)) {
        foreach ($users as $user) {
            $addition = '<option value="' . $user . '">'
                . $user . '</options>';
            $option_list .= $addition;
        }
    }
    return $option_list;
}
