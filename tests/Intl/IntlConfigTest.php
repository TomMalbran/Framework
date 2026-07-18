<?php
namespace Tests\Intl;

use Framework\Application;
use Framework\Intl\IntlConfig;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IntlConfigTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Intl/.tmp_intlconfig";

    private const Strings       = [ "HELLO" => "Hola", "BYE" => "Chau" ];
    private const Emails        = [ "WELCOME" => "Welcome {0}" ];
    private const Notifications = [ "ALERT" => "Alert!" ];

    private string $fixtureBase = "";

    /** @var array<string,mixed> */
    private array $original = [];


    protected function setUp(): void {
        foreach ([ "defaultLanguage", "stringsDir", "emailsDir", "notificationsDir" ] as $prop) {
            $this->original[$prop] = $this->getPrivateStaticProperty(IntlConfig::class, $prop);
        }

        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        $this->writeFixture("strings", "en", self::Strings);
        $this->writeFixture("emails", "en", self::Emails);
        $this->writeFixture("notifications", "en", self::Notifications);

        IntlConfig::setStringsDir(self::FixtureDir . "/strings");
        IntlConfig::setEmailsDir(self::FixtureDir . "/emails");
        IntlConfig::setNotificationsDir(self::FixtureDir . "/notifications");
    }

    protected function tearDown(): void {
        foreach ($this->original as $prop => $value) {
            $this->setPrivateStaticProperty(IntlConfig::class, $prop, $value);
        }
        Storage::deleteDir($this->fixtureBase);
    }

    private function writeFixture(string $subDir, string $langCode, array $data): void {
        $dir = $this->fixtureBase . DIRECTORY_SEPARATOR . $subDir;
        Storage::createDir($dir);
        Storage::writeFile($dir . DIRECTORY_SEPARATOR . $langCode . ".json", json_encode($data));
    }


    #[DataProvider("providerDefaultLanguage")]
    public function testDefaultLanguage(string $language): void {
        IntlConfig::setDefaultLanguage($language);
        $this->assertSame($language, IntlConfig::getDefaultLanguage());
    }

    public static function providerDefaultLanguage(): array {
        return [
            "spanish" => [ "es" ],
            "english" => [ "en" ],
            "french"  => [ "fr" ],
        ];
    }


    #[DataProvider("providerGetStringsPath")]
    public function testGetStringsPath(string $dir): void {
        IntlConfig::setStringsDir($dir);
        $this->assertSame(Application::getBasePath($dir), IntlConfig::getStringsPath());
    }

    public static function providerGetStringsPath(): array {
        return [
            "default"    => [ "nls/strings" ],
            "custom"     => [ "custom/lang" ],
            "single_dir" => [ "strings" ],
        ];
    }


    #[DataProvider("providerLoadStrings")]
    public function testLoadStrings(string $langCode, array $expected, bool $isEmpty): void {
        $result = IntlConfig::loadStrings($langCode);
        $this->assertSame($isEmpty, $result->isEmpty());
        $this->assertSame($expected, $result->toArray());
    }

    public static function providerLoadStrings(): array {
        return [
            "existing"    => [ "en", self::Strings, false ],
            "missing_lang" => [ "zz", [], true ],
        ];
    }


    #[DataProvider("providerLoadEmails")]
    public function testLoadEmails(string $langCode, array $expected, bool $isEmpty): void {
        $result = IntlConfig::loadEmails($langCode);
        $this->assertSame($isEmpty, $result->isEmpty());
        $this->assertSame($expected, $result->toArray());
    }

    public static function providerLoadEmails(): array {
        return [
            "existing"    => [ "en", self::Emails, false ],
            "missing_lang" => [ "zz", [], true ],
        ];
    }


    #[DataProvider("providerLoadNotifications")]
    public function testLoadNotifications(string $langCode, array $expected, bool $isEmpty): void {
        $result = IntlConfig::loadNotifications($langCode);
        $this->assertSame($isEmpty, $result->isEmpty());
        $this->assertSame($expected, $result->toArray());
    }

    public static function providerLoadNotifications(): array {
        return [
            "existing"    => [ "en", self::Notifications, false ],
            "missing_lang" => [ "zz", [], true ],
        ];
    }
}
