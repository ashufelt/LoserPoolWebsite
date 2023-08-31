<?php
include_once("user_handler.php");
include_once("../picks/pick_handler.php");

use function UserHandling\uh_add_user;
use function PickHandling\ph_get_picks_html_table;
use function UserHandling\uh_get_user_option_list_html;

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<h4>Invalid Post request</h4>";
}


$additional_info = "";
if (!uh_add_user($_POST['name'], $_POST['username'], $_POST['pin'], $_POST['repin'])) {
    $additional_info = "<p>Could not register the new user. Make sure your pins match, 
                        and that your username is not already taken. If this message 
                        doesn't go away, please refresh the page.</p>";
}

$select_head = "<select hx-swap-oob='true:#userpicks' id='userpicks' name='userpick' 
                      hx-get='teams/all.php' hx-trigger='change, load delay:200ms once'
                      hx-target='#teams' hx-swap='outerHTML'>";

$select_foot = "</select>";

$replace_select_users = $select_head . uh_get_user_option_list_html() . $select_foot;

echo $replace_select_users . $additional_info . ph_get_picks_html_table();
