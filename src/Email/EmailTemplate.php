<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Provider\Mustache;
use Framework\Database\Query;
use Framework\System\Package;
use Framework\System\Config;
use Framework\System\Language;
use Framework\Utils\Strings;
use Framework\Schema\EmailTemplateSchema;
use Framework\Schema\EmailTemplateEntity;

/**
 * The Email Templates
 */
class EmailTemplate extends EmailTemplateSchema {

    /**
     * Returns an Email Template for the Email Sender
     * @param string $templateCode
     * @param string $language     Optional.
     * @return EmailTemplateEntity
     */
    public static function get(string $templateCode, string $language = "root"): EmailTemplateEntity {
        $langCode = Language::getCode($language);
        $query    = Query::create("templateCode", "=", $templateCode);
        $query->add("language", "=", $langCode);
        return self::getEntity($query);
    }



    /**
     * Renders the Email Template message with Mustache
     * @param string  $message
     * @param array{} $data    Optional.
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
     * Migrates the Email Templates data
     * @return boolean
     */
    public static function migrateData(): bool {
        self::truncateData();

        $position  = 0;
        $languages = Language::getAll();
        foreach ($languages as $language => $languageName) {
            $templates = Discovery::loadJSON(Package::EmailsDir, $language);
            if (!empty($templates)) {
                $position = self::migrateLanguage($templates, $language, $languageName, $position);
            }
        }
        return true;
    }

    /**
     * Migrates the Email Templates for the given Language
     * @param array{}[] $templates
     * @param string    $language
     * @param string    $languageName
     * @param integer   $position
     * @return integer
     */
    private static function migrateLanguage(array $templates, string $language, string $languageName, int $position): int {
        $siteName = Config::getName();
        $updates  = [];

        // Adds the Email Templates
        foreach ($templates as $templateCode => $template) {
            $position += 1;
            $message   = Strings::join($template["message"], "\n\n");
            $updates[] = [
                "templateCode" => $templateCode,
                "language"     => $language,
                "languageName" => $languageName,
                "description"  => $template["description"],
                "subject"      => Strings::replace($template["subject"], "[site]", $siteName),
                "message"      => Strings::replace($message, "[site]", $siteName),
                "position"     => $position,
            ];
        }

        // Process the SQL
        if (!empty($updates)) {
            print("<br>Updated <i>" . count($updates) . " emails</i> for language <b>$languageName</b><br>");
            self::batchEntities($updates);
        }

        return $position;
    }
}
