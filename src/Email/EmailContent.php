<?php
namespace Framework\Email;

use Framework\Discovery\DiscoveryMigration;
use Framework\Email\Schema\EmailContentSchema;
use Framework\Email\Schema\EmailContentEntity;
use Framework\Email\Schema\EmailContentQuery;
use Framework\Intl\NLSConfig;
use Framework\Provider\Mustache;
use Framework\System\Config;
use Framework\System\Language;
use Framework\System\EmailCode;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Email Contents
 */
class EmailContent extends EmailContentSchema implements DiscoveryMigration {

    /**
     * Returns an Email Content for the Email Sender
     * @param EmailCode $emailCode
     * @param string    $language  Optional.
     * @return EmailContentEntity
     */
    public static function get(EmailCode $emailCode, string $language = "root"): EmailContentEntity {
        $langCode = Language::getCode($language);

        $query = new EmailContentQuery();
        $query->emailCode->equal($emailCode->name);
        $query->language->equal($langCode);
        return self::getEntity($query);
    }

    /**
     * Renders the Email Content message with Mustache
     * @param string              $message
     * @param array<string,mixed> $data    Optional.
     * @return string
     */
    public static function render(string $message, array $data = []): string {
        $html   = !Strings::contains($message, "</p>\n\n<p>") ? Strings::toHtml($message) : $message;
        $result = Mustache::render($html, $data);

        $result = Strings::replace($result, "<p></p>", "");
        while (Strings::contains($result, "<br><br><br>")) {
            $result = Strings::replace($result, "<br><br><br>", "<br><br>");
        }
        return $result;
    }



    /**
     * Migrates the Email Contents data
     * @return boolean
     */
    #[\Override]
    public static function migrateData(): bool {
        self::truncateData();

        $languages = Language::getAll();
        $position  = 0;
        $didUpdate = false;

        foreach ($languages as $language => $languageName) {
            $emails = NLSConfig::loadEmails($language);
            if (!$emails->isEmpty()) {
                $position  = self::migrateLanguage($emails, $language, $languageName, $position);
                $didUpdate = true;
            }
        }

        if (!$didUpdate) {
            print("- No emails updated\n");
            return false;
        }
        return true;
    }

    /**
     * Migrates the Email Templates for the given Language
     * @param Dictionary $emails
     * @param string     $language
     * @param string     $languageName
     * @param integer    $position
     * @return integer
     */
    private static function migrateLanguage(Dictionary $emails, string $language, string $languageName, int $position): int {
        $siteName = Config::getName();
        $total    = 0;

        foreach ($emails as $emailCode => $email) {
            $message   = Strings::join($email->getStrings("message"), "\n\n");
            $position += 1;
            $total    += 1;

            self::createEntity(
                emailCode:    $emailCode,
                language:     $language,
                languageName: $languageName,
                description:  $email->getString("description"),
                subject:      Strings::replace($email->getString("subject"), "[site]", $siteName),
                message:      Strings::replace($message, "[site]", $siteName),
                position:     $position,
                skipOrder:    true,
            );
        }

        if ($total > 0) {
            print("- Updated $total emails for language $languageName\n");
        }
        return $position;
    }
}
