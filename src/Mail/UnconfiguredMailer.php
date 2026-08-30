<?php

namespace LoserPool\Mail;

/*
 * What you get with no API key set.
 *
 * The pool ran for years with no mail at all, and it has to keep working that
 * way: a missing key is a deployment that has not been finished, not a broken
 * site. Callers ask isConfigured() and tell the player to contact the
 * commissioner instead of promising an email that will never arrive.
 */
final class UnconfiguredMailer implements Mailer
{
    public function send(string $to, string $subject, string $body): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return false;
    }
}
