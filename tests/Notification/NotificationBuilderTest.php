<?php
namespace Tests\Notification;

use Framework\Application;
use Framework\Intl\IntlConfig;
use Framework\Notification\NotificationBuilder;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NotificationBuilderTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Notification/.tmp_notification_builder";

    private string $fixtureBase = "";

    /** @var array<string,mixed> */
    private array $original = [];


    protected function setUp(): void {
        foreach ([ "defaultLanguage", "notificationsDir" ] as $prop) {
            $this->original[$prop] = $this->getPrivateStaticProperty(IntlConfig::class, $prop);
        }

        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
        IntlConfig::setNotificationsDir(self::FixtureDir);
    }

    protected function tearDown(): void {
        foreach ($this->original as $prop => $value) {
            $this->setPrivateStaticProperty(IntlConfig::class, $prop, $value);
        }
        Storage::deleteDir($this->fixtureBase);
    }

    private function writeNotifications(string $langCode, array $data): void {
        Storage::writeFile($this->fixtureBase . DIRECTORY_SEPARATOR . $langCode . ".json", json_encode($data));
    }


    #[DataProvider("providerCollectNotifications")]
    public function testCollectNotifications(?array $notifications, array $expectedCodes): void {
        if ($notifications !== null) {
            $this->writeNotifications("en", $notifications);
        }

        $result = NotificationBuilder::collectNotifications();
        $this->assertSame($expectedCodes, $result["codes"]);
        $this->assertSame(count($expectedCodes), $result["total"]);
    }

    public static function providerCollectNotifications(): array {
        return [
            "multiple_codes" => [
                [
                    "NEW_MESSAGE" => "You have a new message",
                    "NEW_FRIEND"  => "You have a new friend",
                    "REMINDER"    => "Remember to do this",
                ],
                [ "NEW_MESSAGE", "NEW_FRIEND", "REMINDER" ],
            ],
            "single_code"    => [
                [ "ALERT" => "Alert!" ],
                [ "ALERT" ],
            ],
            "empty_file"     => [
                [],
                [],
            ],
            "missing_file"   => [
                null,
                [],
            ],
        ];
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, NotificationBuilder::destroyCode());
    }
}
