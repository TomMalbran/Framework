<?php
namespace Tests\Email;

use Framework\Application;
use Framework\Email\EmailBuilder;
use Framework\Email\EmailSender;
use Framework\System\EmailProvider;
use Framework\Utils\Arrays;
use Framework\Intl\IntlConfig;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EmailBuilderTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Email/.tmp_email_builder";

    private string $fixtureBase = "";

    /** @var array<string,mixed> */
    private array $original = [];


    protected function setUp(): void {
        foreach ([ "defaultLanguage", "emailsDir" ] as $prop) {
            $this->original[$prop] = $this->getPrivateStaticProperty(IntlConfig::class, $prop);
        }

        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
        IntlConfig::setEmailsDir(self::FixtureDir);
    }

    protected function tearDown(): void {
        foreach ($this->original as $prop => $value) {
            $this->setPrivateStaticProperty(IntlConfig::class, $prop, $value);
        }
        Storage::deleteDir($this->fixtureBase);
    }

    private function writeEmails(string $langCode, array $data): void {
        Storage::writeFile($this->fixtureBase . DIRECTORY_SEPARATOR . $langCode . ".json", json_encode($data));
    }


    #[DataProvider("providerCollectEmails")]
    public function testCollectEmails(array $files, array $expectedCodes, int $expectedTotal): void {
        foreach ($files as $langCode => $data) {
            $this->writeEmails($langCode, $data);
        }

        $result = EmailBuilder::collectEmails();
        $this->assertSame($expectedCodes, $result["codes"]);
        $this->assertSame($expectedTotal, $result["total"]);
    }

    public static function providerCollectEmails(): array {
        return [
            "multiple_codes"      => [
                [
                    "en" => [
                        "WELCOME" => [ "subject" => "Welcome" ],
                        "RESET"   => [ "subject" => "Reset" ],
                    ],
                ],
                [ "WELCOME", "RESET" ],
                2,
            ],
            "single_code"         => [
                [
                    "en" => [ "WELCOME" => [ "subject" => "Welcome" ] ],
                ],
                [ "WELCOME" ],
                1,
            ],
            "empty_file"          => [
                [ "en" => [] ],
                [ "Test" ],
                1,
            ],
            "missing_file"        => [
                [],
                [ "Test" ],
                1,
            ],
            "ignores_other_langs" => [
                [
                    "es" => [ "HOLA" => [ "subject" => "Hola" ] ],
                ],
                [ "Test" ],
                1,
            ],
        ];
    }


    public function testDestroyCode(): void {
        // The Email Codes and the Email Providers
        $this->assertSame(2, EmailBuilder::destroyCode());
    }

    public function testCollectSenders(): void {
        $result = EmailBuilder::collectSenders();
        $names  = Arrays::createArray($result["providers"], "name");

        // Every Provider that can send is found, named after its class
        $this->assertSame(
            [ "Mailgun", "Mailjet", "Mandrill", "SMTP", "SendGrid" ],
            $names,
        );
        $this->assertSame(count($names), $result["total"]);
    }

    public function testTheSendersAreTheClassesOfTheProviders(): void {
        $result = EmailBuilder::collectSenders();

        foreach ($result["providers"] as $provider) {
            $this->assertTrue(
                is_subclass_of($provider["class"], EmailSender::class),
                "{$provider["class"]} is not an EmailSender",
            );
            $this->assertStringEndsWith("\\{$provider["name"]}", $provider["class"]);
        }
    }

    public function testEveryProviderOfTheEnumHasItsSender(): void {
        // Which is the enum the build writes from the senders found above
        foreach (EmailProvider::cases() as $provider) {
            if ($provider === EmailProvider::None) {
                $this->assertNull($provider->getSender());
                continue;
            }

            $sender = $provider->getSender();
            $this->assertNotNull($sender);
            $this->assertTrue(is_subclass_of($sender, EmailSender::class));
        }
    }
}
