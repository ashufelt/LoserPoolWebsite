<?php

/*
 * The registration form, or the notice that replaces it.
 *
 * Served rather than sat in index.html because whether it exists at all is a
 * question about the season: registration closes when week one locks. Putting
 * the decision here keeps one copy of the markup and lets the page ask for the
 * current answer, instead of index.html carrying a form that the handler will
 * refuse for the other seventeen weeks of the year.
 */

include_once(__DIR__ . "/../../src/week_manager.php");

if (!lp_registration_is_open()) {
    echo "<p class='hint'>Registration closed when week 1 locked."
        . " The pool is under way, so there is nothing to join until next season.</p>";
    return;
}
?>
<p class="hint">
    Already playing? Just pick above. Registering takes a moment and you only do it
    once a season. Want a second entry? Register again with <code>_2</code> on the end.
</p>

<!--
  Collapsed by default. Most visits are returning players making a pick, and a
  six field form is a lot of page to scroll past to reach the thing they came
  for.
-->
<details class="register-disclosure">
    <summary class="btn btn-secondary">Register a username</summary>
    <form class="register-form" hx-post="/users/add.php" hx-target="#register-status"
        hx-swap="innerHTML"
        hx-on:lp-registered="this.reset(); this.closest('details').open = false">
        <div class="field">
            <label for="name">Name</label>
            <!--
              No pattern. It used to be [\w\s]{3,30}, which rejects an
              apostrophe, a hyphen and a full stop, so O'Brien, Jean-Luc and
              J.R. could not register and were told nothing about why. The
              value is escaped on the way in and is never used as a key.
            -->
            <input type="text" id="name" name="name" required minlength="3" maxlength="30"
                autocomplete="name">
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
            <p class="field-note">Your name and email are never shown to other players.</p>
        </div>

        <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required pattern="\w{3,20}"
                title="3 to 20 characters: letters, numbers and underscores. No spaces."
                autocomplete="username">
            <p class="field-note">3 to 20 characters: letters, numbers and underscores.</p>
        </div>

        <div class="field-row">
            <div class="field field-narrow">
                <label for="pin">PIN</label>
                <input type="password" id="pin" name="pin" required pattern="\d{4}"
                    title="Four digits." inputmode="numeric" maxlength="4"
                    autocomplete="new-password">
            </div>
            <div class="field field-narrow">
                <label for="repin">Repeat PIN</label>
                <input type="password" id="repin" name="repin" required pattern="\d{4}"
                    title="Four digits, the same as above." inputmode="numeric" maxlength="4"
                    autocomplete="new-password">
            </div>
        </div>
        <p class="field-note">Four digits. You will need it every week, so pick something memorable.</p>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </form>
</details>
