<?php

namespace UserHandling;

include_once __DIR__ . "/../bootstrap.php";


function uh_add_user(string $name, string $email, string $new_user, string $pin, string $repin): bool
{
    $store = lp_store();
    $new_user = htmlspecialchars($new_user);
    $name = htmlspecialchars($name);
    $email = htmlspecialchars($email);
    if ($pin != $repin) {
        return false;
    } else if (strlen($new_user) == 0) {
        return false;
    } else if (0 == $store->addUser($name, $email, $new_user, $pin)) {
        return true;
    } else {
        return false;
    }
}

function uh_get_user_option_list_html(): string
{
    $store = lp_store();
    $option_list = '';

    $users = $store->allUsernames();
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
