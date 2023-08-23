<?php

include_once "./db_interface/SqlAccessController.php";
include_once "./picks_handling/pick_handler.php";
include_once "./users_handling/user_handler.php";
include_once "./data/week_manager.php";

use SqlAccess\SqlAccessController;

use function PickHandling\ph_add_pick;
use function PickHandling\ph_clear_picks_table;
use function PickHandling\ph_get_picks_html_table;
use function PickHandling\ph_get_user_picks_html;
use function UserHandling\uh_add_user;
use function UserHandling\uh_clear_users_table;
use function UserHandling\uh_get_user_option_list_html;
use function UserHandling\uh_get_users_html_table;

class Router
{
    private SqlAccessController $controller;

    public function __construct()
    {
        $this->controller = new SqlAccessController();
    }

    public function processRequest(array $params_list, string $method, string $uri)
    {
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        switch ($uri) {
            case "/users/add/":
                if (uh_add_user(
                    $this->controller,
                    $params_list['username'],
                    $params_list['pin'],
                    $params_list['repin']
                )) {
                    return "<h3>User added successfully!</h3><br>";
                } else {
                    return "<h3>User could not be added</h3><br>"
                        . file_get_contents("users_handling/add_user_fail.html");
                }
                break;
            case "/users/?get_users=all":
                return uh_get_users_html_table($this->controller);
                break;
            case "/users/?clear_users=all":
                if (uh_clear_users_table($this->controller)) {
                    return "<h3>User list cleared</h3><br>";
                } else {
                    return "<h3>Failed to clear user list</h3><br>";
                }
                break;
                /*
            * Handle all picks below. Adding, viewing, deleting
            */
            case "/picks/":
                if ($params_list['button'] == 'Submit Pick') {
                    return ph_add_pick(
                        $this->controller,
                        $params_list['userpick'],
                        $params_list['week'],
                        $params_list['team'],
                        $params_list['pickpin']
                    );
                } else if ($params_list['button'] == 'View my picks') {
                    return (ph_get_user_picks_html(
                        $this->controller,
                        $params_list['userpick'],
                        $params_list['pickpin']
                    ));
                } else {
                    return "";
                }
                break;
            case "/picks/?clear_picks=all":
                if (ph_clear_picks_table($this->controller)) {
                    return "<h4>Picks cleared</h4><br>";
                } else {
                    return "<h4>Failed to clear picks list</h4><br>";
                }
                break;
            default:
                return "";
                break;
        }
    }

    public function get_picks_table_html(): string
    {
        return ph_get_picks_html_table($this->controller);
    }

    public function get_user_option_list_html(): string
    {
        return uh_get_user_option_list_html($this->controller);
    }
}
