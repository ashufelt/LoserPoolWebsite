<?php

include_once("pick_handler.php");

use function PickHandling\ph_add_pick;
use function PickHandling\ph_get_picks_html_table;
use function PickHandling\ph_get_user_picks_html;

$pick_add_result = ph_add_pick($_POST['userpick'], $_POST['team'], $_POST['pickpin']);

$picks_table = ph_get_picks_html_table();

$user_view = "<div id='one_set_of_picks'>"
    . ph_get_user_picks_html($_POST['userpick'], $_POST['pickpin'])
    . "</div>";

echo $pick_add_result . $user_view . $picks_table;
