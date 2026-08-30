<?php

/*
 * Shared per-request wiring.
 *
 * The handlers previously each constructed their own SqlAccessController, so
 * a single page could open three connections to do one job. One store per
 * request is enough, and passing an override is how tests substitute an
 * in-memory database.
 */

require_once __DIR__ . '/autoload.php';

use LoserPool\Mail\Mailer;
use LoserPool\Mail\ResendMailer;
use LoserPool\Mail\UnconfiguredMailer;
use LoserPool\Storage\PoolStore;
use LoserPool\Storage\StoreFactory;

function lp_store(?PoolStore $override = null): PoolStore
{
    static $store = null;

    if ($override !== null) {
        $store = $override;
    }

    if ($store === null) {
        $store = StoreFactory::fromEnvironment();
    }

    return $store;
}

/*
 * The mailer, on the same terms as the store: one per request, overridable.
 *
 * Unconfigured is the normal state of a fresh deployment, not an error. The
 * pool has always worked with no mail at all, so a missing key degrades to a
 * mailer that says so rather than to a page that fails.
 */
function lp_mailer(?Mailer $override = null): Mailer
{
    static $mailer = null;

    if ($override !== null) {
        $mailer = $override;
    }

    if ($mailer === null) {
        $key = (string) getenv('LP_RESEND_API_KEY');
        $from = (string) getenv('LP_MAIL_FROM');
        $mailer = ($key !== '' && $from !== '')
            ? new ResendMailer($key, $from)
            : new UnconfiguredMailer();
    }

    return $mailer;
}
