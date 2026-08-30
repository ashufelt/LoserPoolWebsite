<?php

namespace UserHandling;

include_once __DIR__ . "/../bootstrap.php";
include_once __DIR__ . "/../week_manager.php";

use LoserPool\Pool\Standings;
use LoserPool\Storage\PoolStore;


/*
 * $problem comes back with the reason a registration was refused.
 *
 * Every refusal used to look the same from the outside: one sentence listing
 * everything that might have been wrong. The browser catches most of these
 * first, but only if its own rules agree with these, and a request that did
 * not come from the form is not checked at all.
 */
function uh_add_user(
    string $name,
    string $email,
    string $new_user,
    string $pin,
    string $repin,
    ?string &$problem = null
): bool {
    $store = lp_store();
    $problem = null;
    $new_user = htmlspecialchars(trim($new_user));
    $name = htmlspecialchars(trim($name));
    $email = htmlspecialchars(trim($email));

    if ($name === "" || $email === "" || $new_user === "" || $pin === "") {
        $problem = "Fill in every box.";
        return false;
    }

    if ($pin !== $repin) {
        $problem = "The two PINs do not match.";
        return false;
    }

    if (preg_match('/^\d{4}$/', $pin) !== 1) {
        $problem = "A PIN is four digits.";
        return false;
    }

    /*
     * The username is a key: it identifies picks, appears in the dropdown and
     * travels in form posts. The name beside it is free text and is only ever
     * displayed, so it is not held to this.
     */
    if (preg_match('/^\w{3,20}$/', $new_user) !== 1) {
        $problem = "A username is 3 to 20 characters, letters, numbers and underscores, with no spaces.";
        return false;
    }

    $code = $store->addUser($name, $email, $new_user, $pin);
    if ($code === PoolStore::OK) {
        return true;
    }

    $problem = $code === PoolStore::USER_EXISTS
        ? "That username is already taken. Try another, or add _2 on the end for a second entry."
        : "Could not save the registration. Try again.";

    return false;
}

/*
 * A new PIN, mailed to the address the player registered with.
 *
 * A reset rather than a reminder, because there is nothing to remind anyone
 * of: PINs are stored as bcrypt hashes and cannot be read back.
 *
 * The registered address has to be supplied and has to match. Usernames are
 * public -- the pick form lists every one of them in a dropdown -- so without
 * that, resetting another player's PIN would be a prank anyone could pull on
 * a Tuesday afternoon.
 */
function uh_reset_pin(string $usernameIn, string $emailIn, ?bool &$finished = null): string
{
    /*
     * $finished says the request is done with, right or wrong, so the endpoint
     * can tell the form to clear and collapse. It is not "the PIN was reset":
     * a request that did not match a registration gets the same answer, on
     * purpose.
     */
    $finished = false;
    $store = lp_store();
    $mailer = lp_mailer();
    $username = htmlspecialchars(trim($usernameIn));
    $email = trim($emailIn);

    if (!$mailer->isConfigured()) {
        return uh_status_bad(
            "Sending mail is not set up on this site yet, so a PIN cannot be reset here."
            . " Email josephrguardiola@gmail.com from the address you registered with."
        );
    }

    if ($username === "" || $email === "") {
        return uh_status_bad("Enter both your username and the email you registered with.");
    }

    /*
     * One answer whether or not the pair matched. Players were told their
     * address is never shown to anyone else, and a message that distinguished
     * "no such player" from "wrong address" would turn this form into a way to
     * test whether a given address belongs to a given username.
     */
    $sent = "Check your email. If <strong>" . $username . "</strong> is registered to that address,"
        . " a new PIN is on its way to it.";

    $registered = $store->emailFor($username);
    if ($registered === null || strcasecmp(trim($registered), $email) !== 0) {
        $finished = true;
        return uh_status_ok($sent);
    }

    $pin = str_pad((string) random_int(0, 9999), 4, "0", STR_PAD_LEFT);

    $body = "Your Loser Pool PIN has been reset.\n\n"
        . "Username: " . $usernameIn . "\n"
        . "New PIN:  " . $pin . "\n\n"
        . "Your previous PIN no longer works. If you did not ask for this, reply to this email.\n";

    /*
     * Sent before it is stored, deliberately. If the provider is down, the old
     * PIN still works and the player has lost nothing. Storing first would
     * leave someone locked out of a pool they are still playing in, holding a
     * PIN that was never delivered.
     */
    if (!$mailer->send($registered, "Your Loser Pool PIN", $body)) {
        return uh_status_bad(
            "Could not send the email just now. Your existing PIN still works."
            . " Try again in a minute, or email josephrguardiola@gmail.com."
        );
    }

    if ($store->setPin($username, $pin) !== PoolStore::OK) {
        return uh_status_bad(
            "The new PIN could not be saved, so your existing PIN still works."
            . " Email josephrguardiola@gmail.com."
        );
    }

    $finished = true;
    return uh_status_ok($sent);
}

function uh_status_ok(string $message): string
{
    return "<p class='form-status form-status-ok' role='status'>" . $message . "</p>";
}

function uh_status_bad(string $message): string
{
    return "<p class='form-status form-status-bad' role='status'>" . $message . "</p>";
}

/*
 * Players still in come first, then players who are out, labelled as such.
 *
 * Out players are listed rather than removed. Two reasons, and the second is
 * the one that would hurt: a name that is simply absent reads as a lost
 * registration rather than as an elimination, and buy-backs are recorded by
 * hand after the fact, so between a week-one loss and the commissioner running
 * bin/buyback.php a player who has paid to stay in would find themselves
 * unable to submit anything at all.
 */
function uh_get_user_option_list_html(): string
{
    $store = lp_store();

    $users = $store->allUsernames();
    if (!is_countable($users)) {
        return '';
    }
    sort($users, SORT_NATURAL | SORT_FLAG_CASE);

    $standings = Standings::build(
        $store->allPicks(),
        'check_loser',
        $store->buybacks(),
        $users
    );

    $still_in = '';
    $out = '';
    foreach ($users as $user) {
        $row = $standings[$user] ?? null;
        if ($row !== null && $row['status'] === Standings::OUT) {
            $out .= '<option value="' . $user . '" data-out="' . $row['outWeek'] . '">'
                . $user . ' (out &middot; wk ' . $row['outWeek'] . ')</option>';
            continue;
        }
        $still_in .= '<option value="' . $user . '">' . $user . '</option>';
    }

    if ($out === '') {
        return $still_in;
    }

    /*
     * An optgroup rather than styling the options, because that is the part of
     * a native select every browser actually renders differently: option
     * colour is honoured on some desktop browsers and ignored outright on
     * iOS, where most of these picks are made. The group heading greys and
     * separates the eliminated players everywhere.
     *
     * Not disabled. They can still submit, and a week-one buy-back is recorded
     * by hand afterwards, so disabling would lock out a player who has paid to
     * stay in until the commissioner got round to it.
     */
    return $still_in
        . '<optgroup label="Out of the pool">' . $out . '</optgroup>';
}
