<?php

include_once("team_handler.php");

use function TeamHandler\get_team_options_html;

if (array_key_exists('userpick', $_GET)) {
    echo get_team_options_html($_GET['userpick']);
} else {
    echo get_team_options_html();
}
