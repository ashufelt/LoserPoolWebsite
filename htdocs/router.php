<?php

include_once "./db_interface/SqlAccessController.php";
include_once "./picks_handling/pick_handler.php";
include_once "./users_handling/user_handler.php";
include_once "./data/week_manager.php";
#include_once "./db_interface/SqlAccessErrorCode.php";

use SqlAccess\SqlAccessController;
#use SqlAccess\SqlAccessErrorCode;

use function PickHandling\ph_add_pick;
use function PickHandling\ph_clear_picks_table;
use function PickHandling\ph_get_picks_html_table;
use function UserHandling\uh_add_user;
use function UserHandling\uh_clear_users_table;
use function UserHandling\uh_get_user_option_list_html;
use function UserHandling\uh_get_users_html_table;
use const PickHandling\WEEKS;

class Router
{
    private SqlAccessController $controller;

    public function __construct()
    {
        $this->controller = new SqlAccessController("localhost", "root", "M7ajs5grn3!");
        # $this->controller = new SqlAccessController("sql310.infinityfree.com", "if0_34807647", "3pzOJOHlUKkV2d");
    }

    public function processRequest(array $params_list, string $method, string $uri)
    {
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        switch ($uri) {
            case "/users/add/":
                if (uh_add_user($this->controller, $params_list['username'])) {
                    echo "<h3>User added successfully!</h3><br>";
                    include("users_handling/add_user.html");
                } else {
                    echo "<h3>User could not be added</h3><br>";
                    include("users_handling/add_user_fail.html");
                }
                break;
            case "/users/?get_users=all":
                return uh_get_users_html_table($this->controller);
                break;
            case "/users/?clear_users=all":
                if (uh_clear_users_table($this->controller)) {
                    echo "<h3>User list cleared</h3><br>";
                } else {
                    echo "<h3>Failed to clear user list</h3><br>";
                }
                break;
                /*
            * Handle all picks below. Adding, viewing, deleting
            */
            case "/picks/add/":
                if (ph_add_pick($this->controller, $params_list)) {
                    echo "<h3>Pick added successfully!</h3><br>";
                } else {
                    echo "<h3>Could not create pick</h3><br>";
                }
                break;
            case "/picks/?get_picks=all":
                return ph_get_picks_html_table($this->controller);
                break;
            case "/picks/?clear_picks=all":
                if (ph_clear_picks_table($this->controller)) {
                    echo "<h3>Picks cleared</h3><br>";
                } else {
                    echo "<h3>Failed to clear picks list</h3><br>";
                }
                break;
            default:
                include("main/main.html");
                break;
        }
    }

    public function get_user_option_list_html(): string
    {
        return uh_get_user_option_list_html($this->controller);
    }

    public function get_week_options(): string
    {
        $options = "";
        for ($i = 0; $i < WEEKS; $i++) {
            $options .= "<option value='" . ($i + 1) . "'>" . ($i + 1) . "</option>";
        }
        return $options;
    }
}
