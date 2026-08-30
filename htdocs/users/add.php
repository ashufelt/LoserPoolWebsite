<?php
include_once(__DIR__ . "/../../src/Handlers/user_handler.php");
include_once(__DIR__ . "/../../src/Handlers/pick_handler.php");

use function UserHandling\uh_add_user;
use function PickHandling\ph_get_picks_html_table;
use function UserHandling\uh_get_user_option_list_html;

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<h4>Invalid Post request</h4>";
    return;
}

$username = htmlspecialchars($_POST['username'] ?? '');

if (!uh_add_user($_POST['name'], $_POST['email'], $_POST['username'], $_POST['pin'], $_POST['repin'])) {
    /*
     * Failure keeps the form open with what was typed still in it. Retyping
     * six fields to correct one of them is its own reason not to bother.
     */
    echo "<p class='form-status form-status-bad'>Could not register "
        . ($username !== '' ? "<strong>" . $username . "</strong>" : "that username")
        . ". Check that the two PINs match and that the username is not already taken.</p>";
    return;
}

/*
 * Tells the form it succeeded. The form listens for this to clear itself and
 * collapse, which is the whole confirmation that registration worked: before
 * this, a successful registration returned the picks table into a container
 * further down the page and left the filled-in form sitting open, so there was
 * nothing to distinguish it from a submission that had gone nowhere.
 */
header('HX-Trigger: lp-registered');

echo "<p class='form-status form-status-ok'>"
    . "<strong>" . $username . "</strong> is registered."
    . " Make your pick at the top of the page with the PIN you just chose.</p>";

/*
 * Both of these are elsewhere on the page, so they travel out of band: the
 * username list has to gain the new player before they can pick, and the
 * standings table has to show them.
 */
echo "<select hx-swap-oob='true' id='userpicks' name='userpick'
        hx-get='teams/all.php' hx-trigger='change, load delay:200ms once'
        hx-target='#teams' hx-swap='outerHTML'>"
    . uh_get_user_option_list_html()
    . "</select>";

echo ph_get_picks_html_table(true);
