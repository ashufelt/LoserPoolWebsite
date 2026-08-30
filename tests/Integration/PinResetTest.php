<?php

namespace LoserPool\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use LoserPool\Mail\UnconfiguredMailer;
use LoserPool\Storage\SqliteStore;
use LoserPool\Tests\FakeMailer;
use LoserPool\Tests\FakeScheduleSource;
use LoserPool\Tests\FixtureLoader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Handlers/user_handler.php';
require_once __DIR__ . '/../FakeMailer.php';

use function UserHandling\uh_add_user;
use function UserHandling\uh_reset_pin;

/*
 * Resetting a forgotten PIN.
 *
 * A reset and not a reminder: PINs are bcrypt hashes, so there is nothing to
 * look up and send. That makes the failure modes the interesting part. The
 * wrong ones would be handing a new PIN to whoever asked, or replacing a
 * working PIN with one that never left the building.
 */
final class PinResetTest extends TestCase
{
    private SqliteStore $store;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        $this->store = SqliteStore::open(':memory:', '26');
        lp_store($this->store);
        $this->mailer = new FakeMailer();
        lp_mailer($this->mailer);

        lp_schedule_source(new FakeScheduleSource(
            [1 => FixtureLoader::schedule('2026-w01')],
            ['year' => 2026, 'seasonType' => 2, 'week' => 1]
        ));
        lp_clock(new DateTimeImmutable('2026-09-15 10:00:00', new DateTimeZone('America/Chicago')));

        uh_add_user('Joe', 'joe@example.com', 'joeg', '1234', '1234');
    }

    protected function tearDown(): void
    {
        lp_clock(null, true);
    }

    public function testANewPinIsMailedToTheRegisteredAddress(): void
    {
        $result = uh_reset_pin('joeg', 'joe@example.com');

        $this->assertCount(1, $this->mailer->sent);
        $this->assertSame('joe@example.com', $this->mailer->sent[0]['to']);
        $this->assertStringContainsString('form-status-ok', $result);

        $pin = $this->mailer->lastPin();
        $this->assertNotNull($pin, 'the message carries a four digit PIN');
        $this->assertTrue($this->store->verifyPin('joeg', $pin), 'the mailed PIN works');
        $this->assertFalse($this->store->verifyPin('joeg', '1234'), 'the old PIN does not');
    }

    /* Usernames are public: the dropdown lists every one of them. */
    public function testAWrongEmailResetsNothingAndSendsNothing(): void
    {
        uh_reset_pin('joeg', 'someone-else@example.com');

        $this->assertSame([], $this->mailer->sent);
        $this->assertTrue($this->store->verifyPin('joeg', '1234'), 'the PIN is untouched');
    }

    /*
     * The same answer either way. Players were told their address is never
     * shown to other players, and a reply that distinguished the two cases
     * would turn this form into a way to test an address against a username.
     */
    public function testTheAnswerDoesNotRevealWhetherTheAddressMatched(): void
    {
        $matched = uh_reset_pin('joeg', 'joe@example.com');
        $missed = uh_reset_pin('joeg', 'someone-else@example.com');
        $unknown = uh_reset_pin('nobody', 'joe@example.com');

        $this->assertSame($matched, $missed);
        $this->assertStringContainsString('nobody', $unknown, 'it echoes what was asked for');
        $this->assertStringContainsString('form-status-ok', $unknown);
    }

    /*
     * The order that matters. If the provider is down, the player still has a
     * PIN that works; storing first would leave them locked out of a pool they
     * are still playing in, holding a PIN that was never delivered.
     */
    public function testAFailedSendLeavesTheExistingPinWorking(): void
    {
        lp_mailer(new FakeMailer(true, false));

        $result = uh_reset_pin('joeg', 'joe@example.com');

        $this->assertStringContainsString('form-status-bad', $result);
        $this->assertStringContainsString('existing PIN still works', $result);
        $this->assertTrue($this->store->verifyPin('joeg', '1234'));
    }

    /* No API key is an unfinished deployment, not a broken page. */
    public function testWithNoMailerConfiguredItSaysSoRatherThanPromisingAnEmail(): void
    {
        lp_mailer(new UnconfiguredMailer());

        $result = uh_reset_pin('joeg', 'joe@example.com');

        $this->assertStringContainsString('not set up', $result);
        $this->assertStringContainsString('josephrguardiola@gmail.com', $result);
        $this->assertTrue($this->store->verifyPin('joeg', '1234'));
    }

    public function testAMissingFieldIsAskedForRatherThanGuessedAt(): void
    {
        $result = uh_reset_pin('joeg', '');

        $this->assertStringContainsString('form-status-bad', $result);
        $this->assertSame([], $this->mailer->sent);
    }
}
