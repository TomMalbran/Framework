<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Email\Schema\EmailTemplateSchema;
use Framework\Email\Schema\EmailTemplateEntity;
use Framework\Email\Schema\EmailTemplateQuery;
use Framework\Provider\Mustache;
use Framework\System\Package;
use Framework\System\Config;
use Framework\System\Language;
use Framework\System\Template;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Email Templates
 */
class EmailTemplate extends EmailTemplateSchema {

    /**
     * Returns an Email Template for the Email Sender
     * @param Template $template
     * @param string   $language Optional.
     * @return EmailTemplateEntity
     */
    public static function get(Template $template, string $language = "root"): EmailTemplateEntity {
        $langCode = Language::getCode($language);

        $query = new EmailTemplateQuery();
        $query->templateCode->equal($template->value);
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
            /** @var array<string,string>[] */
            $templates = Discovery::loadJSON(Package::EmailsDir, $language);

            if (!Arrays::isEmpty($templates)) {
                $position = self::migrateLanguage($templates, $language, $languageName, $position);
            }
        }
        return true;
    }

    /**
     * Migrates the Email Templates for the given Language
     * @param array<string,string>[] $templates
     * @param string                 $language
     * @param string                 $languageName
     * @param integer                $position
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
                skipOrder:    true,
            );
        }

        if ($total > 0) {
            print("- Updated $total emails for language $languageName\n");
        }
        return $position;
    }
}
