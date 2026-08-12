<?php
namespace Tests\Email;

use Tests\Email\Fixture\TestEmailSender;

use PHPUnit\Framework\TestCase;

/**
 * The Test Sender, which keeps the emails rather than delivering them
 */
class TestEmailSenderTest extends TestCase {

    protected function setUp(): void {
        TestEmailSender::reset();
    }

    protected function tearDown(): void {
        TestEmailSender::reset();
    }

    /**
     * Hands an email to the sender
     * @param string $toEmail Optional.
     * @return bool
     */
    private function send(string $toEmail = "someone@framework.test"): bool {
        return TestEmailSender::sendEmail(
            $toEmail,
            "hi@framework.test",
            "The Site",
            "reply@framework.test",
            "The subject",
            "The body",
        );
    }



    public function testAnEmailIsKept(): void {
        $this->assertTrue($this->send());

        $this->assertSame(1, TestEmailSender::getCount());
        $this->assertSame("someone@framework.test", TestEmailSender::getLast()["toEmail"]);
        $this->assertSame("hi@framework.test", TestEmailSender::getLast()["fromEmail"]);
        $this->assertSame("The Site", TestEmailSender::getLast()["fromName"]);
        $this->assertSame("reply@framework.test", TestEmailSender::getLast()["replyTo"]);
        $this->assertSame("The subject", TestEmailSender::getLast()["subject"]);
        $this->assertSame("The body", TestEmailSender::getLast()["body"]);
    }

    public function testTheLastOneIsTheLastSent(): void {
        $this->send("first@framework.test");
        $this->send("second@framework.test");

        $this->assertSame(2, TestEmailSender::getCount());
        $this->assertSame("second@framework.test", TestEmailSender::getLast()["toEmail"]);
        $this->assertCount(2, TestEmailSender::getAll());
    }

    public function testThereIsNoLastOneBeforeAnyAreSent(): void {
        $this->assertSame(0, TestEmailSender::getCount());
        $this->assertSame("", TestEmailSender::getLast()["toEmail"]);
        $this->assertSame("", TestEmailSender::getLast()["body"]);
    }

    public function testResettingForgetsThem(): void {
        $this->send();
        TestEmailSender::reset();

        $this->assertSame(0, TestEmailSender::getCount());
        $this->assertCount(0, TestEmailSender::getAll());
    }

    public function testASendCanBeMadeToRefuse(): void {
        TestEmailSender::setSends(false);

        $this->assertFalse($this->send());

        // It was handed over just the same, so it is kept
        $this->assertSame(1, TestEmailSender::getCount());
    }

    public function testTheRefusingIsForgottenToo(): void {
        TestEmailSender::setSends(false);
        TestEmailSender::reset();

        $this->assertTrue($this->send());
    }
}
