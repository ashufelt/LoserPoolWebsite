<?php

namespace LoserPool\Mail;

/*
 * Resend's HTTP API.
 *
 * An HTTPS POST rather than SMTP, for two reasons. Fly.io blocks outbound port
 * 25, so a direct MTA is not an option; and Composer is dev-only here, so
 * there is no PHPMailer to lean on and SMTP would mean hand-rolling STARTTLS
 * and AUTH. This is one request with the same cURL path the ESPN client
 * already uses.
 */
final class ResendMailer implements Mailer
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $from;

    /** @var int */
    private $timeoutSeconds;

    public function __construct(string $apiKey, string $from, int $timeoutSeconds = 8)
    {
        $this->apiKey = $apiKey;
        $this->from = $from;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->from !== '';
    }

    public function send(string $to, string $subject, string $body): bool
    {
        if (!$this->isConfigured() || !function_exists('curl_init')) {
            return false;
        }

        $payload = json_encode([
            'from' => $this->from,
            'to' => [$to],
            'subject' => $subject,
            'text' => $body,
        ]);

        if ($payload === false) {
            return false;
        }

        $handle = curl_init(self::ENDPOINT);
        if ($handle === false) {
            return false;
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        /*
         * The response body is deliberately not surfaced to the player. It can
         * carry the recipient address back, and this endpoint is reachable by
         * anyone who can guess a username.
         */
        return $response !== false && $status >= 200 && $status < 300;
    }
}
