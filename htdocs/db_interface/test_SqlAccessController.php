<?php

include 'SqlAccessController.php';
include 'SqlAccessErrorCode.php';

use SqlAccess\SqlAccessController;
use SqlAccess\SqlAccessErrorCode;

$servername = "localhost";
$username = "root";
$password = "M7ajs5grn3!";

$controller = new SqlAccessController($servername, $username, $password);

echo "Controller error: "
    . $controller->check_connection_error() . "\n";

echo "get_user_table = "
    . print_r($controller->get_user_table(), true) . "\n";

$controller->add_pick('aaa', 'Atlanta Falcons', 1);

echo "get_pick_table = "
    . print_r($controller->get_pick_table(), true) . "\n";

echo "user_exists('aaa') expects true = "
    . print_r($controller->user_exists('aaa'), true) . "\n";

echo "user_exists('abcdef') expects false = "
    . print_r($controller->user_exists('abcdef'), true) . "\n";

echo "get_user_pick_for_week('aaa', 1) expects Atlanta Falcons = "
    . $controller->get_user_pick_for_week('aaa', 1) . "\n";

echo "add_pick('aaa', 'Minnesota Vikings', 1)\n";
$controller->add_pick('aaa', 'Minnesota Vikings', 1);

echo "get_user_pick_for_week('aaa', 1) expects Minnesota Vikings = "
    . $controller->get_user_pick_for_week('aaa', 1) . "\n";

echo "get_user_pick_for_week('abcdef', 1) expects empty string = "
    . $controller->get_user_pick_for_week('abcdef', 1) . "\n";

echo "get_user_pick_for_week('aaa', 4) expects empty string = "
    . $controller->get_user_pick_for_week('aaa', 4) . "\n";

echo "get_user_all_picks(string 'aaa') = "
    . print_r($controller->get_user_all_picks('aaa'), true) . "\n";

echo "get_user_pick_for_week('aaa', 1) expects Atlanta Falcons = "
    . $controller->get_user_pick_for_week('aaa', 1) . "\n";

echo "get_user_all_picks(string 'bbb') = "
    . print_r($controller->get_user_all_picks('bbb'), true) . "\n";

echo "get_user_pick_for_week('bbb', 1) expects Cincinatti Bengals = "
    . $controller->get_user_pick_for_week('bbb', 1) . "\n";

echo "get_user_pick_for_week('bbb', 2) expects no pick = "
    . $controller->get_user_pick_for_week('bbb', 2) . "\n";

$controller->disconnect();
