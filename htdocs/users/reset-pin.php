<?php
include_once(__DIR__ . "/../../src/Handlers/user_handler.php");

use function UserHandling\uh_reset_pin;

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<p class='form-status form-status-bad' role='status'>Invalid request.</p>";
    return;
}

$finished = false;
$result = uh_reset_pin($_POST['resetuser'] ?? '', $_POST['resetemail'] ?? '', $finished);

/*
 * Clears and collapses the form. Sent whenever the request is done with,
 * including for a username and email that did not match: the answer is the
 * same either way, so the form behaves the same way too.
 */
if ($finished) {
    header('HX-Trigger: lp-pin-reset');
}

echo $result;
