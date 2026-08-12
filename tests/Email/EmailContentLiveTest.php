<?php
namespace Tests\Email;

use Framework\Application;
use Framework\Email\EmailContent;
use Framework\File\Storage;
use Framework\System\Config;
use Framework\Intl\IntlConfig;
use Framework\System\EmailCode;

use Tests\LiveTestCase;
use Tests\TestHelpers;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Email Contents, the text of each email in each language
 *
 * The strings live in a JSON per language, which this writes into a directory
 * of its own so the migration has something to find. This repository ships
 * none, and the one code it knows is Test.
 */
class EmailContentLiveTest extends LiveTestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Email/.tmp_email_content";

    private string $fixtureBase = "";
    private mixed  $emailsDir   = null;
    private mixed  $name        = null;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->emailsDir   = $this->getPrivateStaticProperty(IntlConfig::class, "emailsDir");
        $this->name        = Config::getName();
        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
        IntlConfig::setEmailsDir(self::FixtureDir);

        $this->query("DELETE FROM `email_content`");
    }

    protected function tearDown(): void {
        $this->setPrivateStaticProperty(IntlConfig::class, "emailsDir", $this->emailsDir);
        $this->setConfig("NAME", $this->name);
        Storage::deleteDir($this->fixtureBase);
    }

    /**
     * Writes the strings of a language, the way an app ships them
     * @param string              $langCode
     * @param array<string,mixed> $emails
     * @return void
     */
    private function writeEmails(string $langCode, array $emails): void {
        Storage::writeFile(
            $this->fixtureBase . DIRECTORY_SEPARATOR . $langCode . ".json",
            (string)json_encode($emails),
        );
    }

    /**
     * Writes the one email this repository has a code for
     * @param string $subject Optional.
     * @param string $message Optional.
     * @return void
     */
    private function writeTestEmail(
        string $subject = "A subject",
        string $message = "A message",
    ): void {
        $this->writeEmails("en", [
            "Test" => [
                "description" => "The email of the tests",
                "subject"     => $subject,
                "message"     => [ $message ],
            ],
        ]);
    }

    /**
     * Runs the migration, which prints what it did
     * @return string
     */
    private function migrate(): string {
        ob_start();
        try {
            EmailContent::migrateData();
        } finally {
            $output = ob_get_clean();
        }
        return (string)$output;
    }



    public function testTheStringsBecomeRows(): void {
        $this->writeTestEmail();

        $this->assertStringContainsString("Updated 1 emails", $this->migrate());

        $content = EmailContent::get(EmailCode::Test, "en");
        $this->assertSame("A subject", $content->subject);
        $this->assertSame("A message", $content->message);
        $this->assertSame("The email of the tests", $content->description);
        $this->assertSame("English", $content->languageName);
        $this->assertSame(1, $content->position);
    }

    public function testThereIsNothingToUpdate(): void {
        $this->assertStringContainsString("No emails updated", $this->migrate());

        $this->assertFalse(EmailContent::get(EmailCode::Test, "en")->exists());
    }

    public function testTheRowsAreWrittenAgain(): void {
        $this->writeTestEmail();
        $this->migrate();

        $this->writeTestEmail("Another subject", "Another message");
        $this->migrate();

        $this->assertSame(1, EmailContent::getEntityTotal());
        $this->assertSame("Another subject", EmailContent::get(EmailCode::Test, "en")->subject);
    }

    public function testTheParagraphsAreJoined(): void {
        $this->writeEmails("en", [
            "Test" => [
                "subject" => "A subject",
                "message" => [ "One", "Two" ],
            ],
        ]);
        $this->migrate();

        $this->assertSame("One\n\nTwo", EmailContent::get(EmailCode::Test, "en")->message);
    }

    public function testTheSiteNameIsWrittenIn(): void {
        $this->setConfig("NAME", "The Site");
        $this->writeTestEmail("Welcome to [site]", "This is [site] writing");
        $this->migrate();

        $content = EmailContent::get(EmailCode::Test, "en");
        $this->assertSame("Welcome to The Site", $content->subject);
        $this->assertSame("This is The Site writing", $content->message);
    }

    public function testALanguageFallsBackToTheRoot(): void {
        $this->writeTestEmail();
        $this->migrate();

        // Only en is set up here, and it is the root, so asking in any other
        // language comes back with it rather than with nothing
        $this->assertSame("A subject", EmailContent::get(EmailCode::Test, "pt")->subject);
    }

    public function testNoCodeAtAllFindsNothing(): void {
        // The condition of an Enum drops the case that has no value, which
        // would leave the query asking for every code, so the code is looked
        // up by its name instead
        $this->writeTestEmail();
        $this->migrate();

        $this->assertFalse(EmailContent::get(EmailCode::None, "en")->exists());
    }



    /**
     * A message as it is written, and the HTML it is rendered into
     * @param string $message
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerRender")]
    public function testTheMessageIsRendered(string $message, string $expected): void {
        $this->assertSame($expected, EmailContent::render($message));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerRender(): array {
        return [
            "a line break"          => [ "One\nTwo", "One<br>Two" ],
            "a paragraph"           => [ "One\n\nTwo", "One<br><br>Two" ],
            "three breaks are two"  => [ "One\n\n\nTwo", "One<br><br>Two" ],
            "four breaks are two"   => [ "One\n\n\n\nTwo", "One<br><br>Two" ],
            "html is left as it is" => [ "<p>One</p>\n\n<p>Two</p>", "<p>One</p>\n\n<p>Two</p>" ],
            "an empty paragraph"    => [ "<p></p>\n\n<p>One</p>", "\n\n<p>One</p>" ],
            "nothing"               => [ "", "" ],
        ];
    }

    public function testTheMessageIsFilledIn(): void {
        $this->assertSame(
            "Hello Ana",
            EmailContent::render("Hello {{name}}", [ "name" => "Ana" ]),
        );
    }
}
