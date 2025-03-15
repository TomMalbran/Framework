<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Provider\Mustache;
use Framework\System\Package;
use Framework\System\Config;
use Framework\System\Language;
use Framework\Utils\Strings;
use Framework\Schema\EmailTemplateSchema;
use Framework\Schema\EmailTemplateEntity;
use Framework\Schema\EmailTemplateQuery;

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

        $query = new EmailTemplateQuery();
        $query->templateCode->equal($templateCode);
        $query->language->equal($langCode);
        return self::getEntity($query);
    }



    /**
     * Renders the Email Template message with Mustache
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
        $total    = 0;

        foreach ($templates as $templateCode => $template) {
            $message   = Strings::join($template["message"], "\n\n");
            $position += 1;
            $total    += 1;

            self::createEntity(
                templateCode: $templateCode,
                language:     $language,
                languageName: $languageName,
                description:  $template["description"],
                subject:      Strings::replace($template["subject"], "[site]", $siteName),
                message:      Strings::replace($message, "[site]", $siteName),
                position:     $position,
            );
        }

        if ($total > 0) {
            print("<br>Updated <i>$total emails</i> for language <b>$languageName</b><br>");
        }
        return $position;
    }
}
