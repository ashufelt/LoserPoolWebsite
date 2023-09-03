<?php

namespace UserHandling;

include_once "../SqlAccess/SqlAccessController.php";

use SqlAccess\SqlAccessController;

function uh_add_user(string $name, string $email, string $new_user, string $pin, string $repin): bool
{
    $controller = new SqlAccessController();
    $new_user = htmlspecialchars($new_user);
    $name = htmlspecialchars($name);
    $email = htmlspecialchars($email);
    if ($pin != $repin) {
        return false;
    } else if (strlen($new_user) == 0) {
        return false;
    } else if (0 == $controller->add_user($name, $email, $new_user, intval($pin))) {
        return true;
    } else {
        return false;
    }
}

function uh_get_users_html_table(): string
{
    $controller = new SqlAccessController();
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

function uh_clear_users_table(): bool
{
    $controller = new SqlAccessController();
    if (0 == $controller->clear_user_table()) {
        return true;
    } else {
        return false;
    }
}

function uh_get_user_option_list_html(): string
{
    $controller = new SqlAccessController();
    $option_list = '';

    $users = $controller->get_user_table();
    sort($users, SORT_NATURAL | SORT_FLAG_CASE);
    $option_list .= "";
    if (is_countable($users)) {
        foreach ($users as $user) {
            $addition = '<option value="' . $user . '">'
                . $user . '</options>';
            $option_list .= $addition;
        }
    }
    return $option_list;
}
