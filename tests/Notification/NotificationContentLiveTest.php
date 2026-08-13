<?php
namespace Tests\Notification;

use Framework\Application;
use Framework\File\Storage;
use Framework\Intl\IntlConfig;
use Framework\Notification\NotificationContent;
use Framework\System\NotificationCode;

use Tests\LiveTestCase;
use Tests\TestHelpers;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Notification Contents, the text of each push in each language
 *
 * The strings live in a JSON per language, which this writes into a directory
 * of its own so the migration has something to find. This repository ships
 * none, and the one code it knows is None.
 */
class NotificationContentLiveTest extends LiveTestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Notification/.tmp_content";

    private string $fixtureBase      = "";
    private mixed  $notificationsDir = null;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->notificationsDir = $this->getPrivateStaticProperty(
            IntlConfig::class,
            "notificationsDir",
        );
        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
        IntlConfig::setNotificationsDir(self::FixtureDir);

        $this->query("DELETE FROM `notification_content`");
    }

    protected function tearDown(): void {
        $this->setPrivateStaticProperty(
            IntlConfig::class,
            "notificationsDir",
            $this->notificationsDir,
        );
        Storage::deleteDir($this->fixtureBase);
    }

    /**
     * Writes the strings of a language, the way an app ships them
     * @param string              $langCode
     * @param array<string,mixed> $notifications
     * @return void
     */
    private function writeNotifications(string $langCode, array $notifications): void {
        Storage::writeFile(
            $this->fixtureBase . DIRECTORY_SEPARATOR . $langCode . ".json",
            (string)json_encode($notifications),
        );
    }

    /**
     * Writes the one notification this repository has a code for
     * @param string $title   Optional.
     * @param string $message Optional.
     * @return void
     */
    private function writeNotification(
        string $title = "A title",
        string $message = "A message",
    ): void {
        $this->writeNotifications("en", [
            "None" => [
                "description" => "The push of the tests",
                "title"       => $title,
                "message"     => $message,
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
            NotificationContent::migrateData();
        } finally {
            $output = ob_get_clean();
        }
        return (string)$output;
    }



    public function testTheStringsBecomeRows(): void {
        $this->writeNotification();

        $this->assertStringContainsString("Updated 1 notifications", $this->migrate());

        $content = NotificationContent::get(NotificationCode::None, "en");
        $this->assertSame("A title", $content->title);
        $this->assertSame("A message", $content->message);
        $this->assertSame("The push of the tests", $content->description);
        $this->assertSame("English", $content->languageName);
        $this->assertSame(1, $content->position);
    }

    public function testThereIsNothingToUpdate(): void {
        $this->assertStringContainsString("No notifications updated", $this->migrate());

        $this->assertFalse(NotificationContent::get(NotificationCode::None, "en")->exists());
    }

    public function testTheRowsAreWrittenAgain(): void {
        $this->writeNotification();
        $this->migrate();

        $this->writeNotification("Another title");
        $this->migrate();

        $this->assertSame(1, NotificationContent::getEntityTotal());
        $this->assertSame(
            "Another title",
            NotificationContent::get(NotificationCode::None, "en")->title,
        );
    }

    public function testEachCodeIsARowOfItsOwn(): void {
        $this->writeNotifications("en", [
            "None"  => [ "title" => "The first" ],
            "Other" => [ "title" => "The second" ],
        ]);

        $this->assertStringContainsString("Updated 2 notifications", $this->migrate());

        $this->assertSame(2, NotificationContent::getEntityTotal());
        $this->assertSame("The first", NotificationContent::get(NotificationCode::None)->title);
    }

    public function testALanguageFallsBackToTheRoot(): void {
        $this->writeNotification();
        $this->migrate();

        // Only en is set up here, and it is the root, so asking in any other
        // language comes back with it rather than with nothing
        $this->assertSame(
            "A title",
            NotificationContent::get(NotificationCode::None, "pt")->title,
        );
    }



    /**
     * A message as it is written, and what it is rendered into
     * @param string              $message
     * @param array<string,mixed> $data
     * @param string              $expected
     * @return void
     */
    #[DataProvider("providerRender")]
    public function testTheMessageIsRendered(
        string $message,
        array $data,
        string $expected,
    ): void {
        $this->assertSame($expected, NotificationContent::render($message, $data));
    }

    /**
     * @return array<string,array{string,array<string,mixed>,string}>
     */
    public static function providerRender(): array {
        return [
            "a value"             => [ "Hello {{name}}", [ "name" => "Ana" ], "Hello Ana" ],
            "one that is missing" => [ "Hello {{name}}", [], "Hello " ],
            "nothing to fill in"  => [ "Hello", [ "name" => "Ana" ], "Hello" ],
            "the breaks are kept" => [ "One\nTwo", [], "One\nTwo" ],
            "nothing"             => [ "", [], "" ],
        ];
    }
}
