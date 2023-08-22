<!doctype html>
<html>

<?php

use function PickHandling\ph_get_picks_html_table;

require "router.php";
$router = new Router();

include_once "template/header.html";
include_once "data/team_options.php";
include_once "data/week_manager.php";
include_once "users_handling/user_handler.php";
?>

<body>
    <?php
    include "template/banner.html";
    ?>
    <main>
        <br>
        <div class="user_adding">
            <h3> Register Username </h3>
            <form action="/users/add/" method="post">
                <table class="user_adding">
                    <tr class="user_adding">
                        <td class="user_adding cell_display_right">
                            <label for="username">Username: (3-20 chars)</label>
                        </td>
                        <td class="user_adding cell_display_left">
                            <input type="text" id="username" name="username" required pattern="\w{3,20}">
                        </td>
                        <td><input type="submit" value="Submit"></td>
                    </tr>
                    <tr class="user_adding">
                        <td class="user_adding cell_display_right">
                            <label for="pin">PIN (4 digits):</label>
                        </td>
                        <td class="user_adding cell_display_left">
                            <input type="text" id="pin" name="pin" required pattern="\d{4}">
                        </td>
                    </tr>
                    <tr class="user_adding">
                        <td class="user_adding cell_display_right">
                            <label for="repin">Repeat PIN:</label>
                        </td>
                        <td class="user_adding cell_display_left">
                            <input type="text" id="repin" name="repin" required pattern="\d{4}">
                        </td>
                    </tr>
                </table>
            </form>
            <!--
            <form action="/users/" method="get">
                <button name="get_users" value="all">Get list of Users</button>
            </form>
            --!>
            <form id="clear_user_button" action="/users/" method="get">
                <button name="clear_users" value="all">Clear list of Users</button>
            </form>
        </div>
        <br><br>
        <div class="pick_adding">
            <h3> Make a pick for Week <?php echo get_current_week() ?></h3>
            <form action="/picks/add/" method="post">
                <label for="userpick">Username:</label>
                <select id="userpick" name="userpick" required>
                    <?php echo $router->get_user_option_list_html(); ?>
                </select>
                <input type="hidden" name="week" value=<?php echo get_current_week() ?>>
                </select>
                <label for="team">Losing Team:</label>
                <select id="team" name="team" required>
                    <?php echo get_team_options() ?>
                </select>
                <input type="submit" value="Submit Pick">
            </form>
            <form id="clear_picks_button" class="clear_button" action="/picks/" method="get">
                <button name="clear_picks" value="all">Clear list of Picks</button>
            </form>
        </div>
        <br><br>
        <?php echo $router->processRequest($_REQUEST, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']); ?>
        <?php echo $router->get_picks_table_html(); ?>
    </main>
    <?php include "template/footer.html"; ?>
</body>

</html>
