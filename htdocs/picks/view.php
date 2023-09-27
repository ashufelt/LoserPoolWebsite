<?php

include("pick_handler.php");

use function PickHandling\ph_get_user_picks_html;

echo ph_get_user_picks_html($_POST['userpick'], $_POST['pickpin']);
