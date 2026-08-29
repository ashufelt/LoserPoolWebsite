<?php

include_once(__DIR__ . "/../../src/Handlers/pick_handler.php");

use function PickHandling\ph_get_picks_html_table;

echo ph_get_picks_html_table();
