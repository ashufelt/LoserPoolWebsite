<?php

namespace LoserPool\Mail;

/*
 * Sending one message to one player.
 *
 * An interface because the pool has exactly one reason to send mail -- a PIN
 * it can no longer read back -- and no reason to grow a mail subsystem. It
 * also keeps the tests off the network: the suite runs with a fake in place.
 */
interface Mailer
{
    /*
     * True if the provider accepted the message. False is a normal outcome,
     * not an exception: mail is the one part of this that depends on a service
     * outside the container, and a page must not fatal because it is down.
     */
    public function send(string $to, string $subject, string $body): bool;

    /* Whether sending is configured at all, so callers can say so up front. */
    public function isConfigured(): bool;
}
