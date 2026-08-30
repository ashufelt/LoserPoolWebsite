<?php

namespace LoserPool\Tests;

use LoserPool\Mail\Mailer;

/*
 * Records what would have been sent. The suite must not reach the network, and
 * the interesting assertions are about the message: that a new PIN went to the
 * registered address and to nowhere else.
 */
final class FakeMailer implements Mailer
{
    /** @var array<int,array{to:string,subject:string,body:string}> */
    public $sent = [];

    /** @var bool */
    private $configured;

    /** @var bool */
    private $accepts;

    public function __construct(bool $configured = true, bool $accepts = true)
    {
        $this->configured = $configured;
        $this->accepts = $accepts;
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        if (!$this->accepts) {
            return false;
        }

        $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
        return true;
    }

    /* The PIN out of the most recent message, so a test can try logging in. */
    public function lastPin(): ?string
    {
        $last = end($this->sent);
        if ($last === false || !preg_match('/New PIN:\s+(\d{4})/', $last['body'], $m)) {
            return null;
        }
        return $m[1];
    }
}
