<?php

namespace UserHandling;

include_once __DIR__ . "/../bootstrap.php";
include_once __DIR__ . "/../week_manager.php";

use LoserPool\Pool\Standings;


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

/*
 * Players still in come first, then players who are out, labelled as such.
 *
 * Out players are listed rather than removed. Two reasons, and the second is
 * the one that would hurt: a name that is simply absent reads as a lost
 * registration rather than as an elimination, and buy-backs are recorded by
 * hand after the fact, so between a week-one loss and the commissioner running
 * bin/buyback.php a player who has paid to stay in would find themselves
 * unable to submit anything at all.
 */
function uh_get_user_option_list_html(): string
{
    $store = lp_store();

    $users = $store->allUsernames();
    if (!is_countable($users)) {
        return '';
    }
    sort($users, SORT_NATURAL | SORT_FLAG_CASE);

    $standings = Standings::build(
        $store->allPicks(),
        'check_loser',
        $store->buybacks(),
        $users
    );

    $still_in = '';
    $out = '';
    foreach ($users as $user) {
        $row = $standings[$user] ?? null;
        if ($row !== null && $row['status'] === Standings::OUT) {
            $out .= '<option value="' . $user . '">' . $user
                . ' (out &middot; wk ' . $row['outWeek'] . ')</option>';
            continue;
        }
        $still_in .= '<option value="' . $user . '">' . $user . '</option>';
    }

    return $still_in . $out;
}
