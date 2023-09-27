<?php

include_once("user_handler.php");

use function UserHandling\uh_get_user_option_list_html;

$select_head = " <select id='userpicks' name='userpick' 
                      hx-get='teams/all.php' hx-trigger='change, load delay:200ms once'
                      hx-target='#teams' hx-swap='outerHTML'>";

$select_foot = "</select>";

echo $select_head . uh_get_user_option_list_html() . $select_foot;
