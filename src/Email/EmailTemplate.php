<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\System\ConfigCode;
use Framework\NLS\Language;
use Framework\Provider\Mustache;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Database;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Utils\Strings;

/**
 * The Email Templates
 */
class EmailTemplate {

    /**
     * Loads the Email Templates Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("emailTemplates");
    }



    /**
     * Returns an Email Template for the Email Sender
     * @param string $templateCode
     * @param string $language     Optional.
     * @return Model
     */
    public static function get(string $templateCode, string $language = "root"): Model {
        $langCode = Language::getCode($language);
        $query    = Query::create("templateCode", "=", $templateCode);
        $query->add("language", "=", $langCode);
        return self::schema()->getOne($query);
    }

    /**
     * Returns an Email Template with the given ID
     * @param integer $templateID
     * @return Model
     */
    public static function getOne(int $templateID): Model {
        $query = Query::create("TEMPLATE_ID", "=", $templateID);
        return self::schema()->getOne($query);
    }

    /**
     * Returns true if there is an Email Template with ID
     * @param string $templateID
     * @return boolean
     */
    public static function exists(string $templateID): bool {
        $query = Query::create("TEMPLATE_ID", "=", $templateID);
        return self::schema()->exists($query);
    }



    /**
     * Returns all the Email Templates
     * @param Request|null $request Optional.
     * @return array{}[]
     */
    public static function getAll(?Request $request = null): array {
        return self::schema()->getAll(null, $request);
    }

    /**
     * Returns the total amount of Email Templates
     * @return integer
     */
    public static function getTotal(): int {
        return self::schema()->getTotal();
    }

    /**
     * Edits the given Email Template
     * @param integer $templateID
     * @param Request $request
     * @return boolean
     */
    public static function edit(int $templateID, Request $request): bool {
        return self::schema()->edit($templateID, $request);
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
     * Migrates the Email Templates
     * @return boolean
     */
    public static function migrate(): bool {
        $db = Framework::getDatabase();
        if (!$db->hasTable("email_templates")) {
            return false;
        }
        $db->truncate("email_templates");

        $position  = 0;
        $languages = Language::getAll();
        foreach ($languages as $language => $languageName) {
            $templates = Framework::loadJSON(Framework::EmailsDir, $language);
            if (!empty($templates)) {
                $position = self::migrateLanguage($db, $templates, $language, $languageName, $position);
            }
        }
        return true;
    }

    /**
     * Migrates the Email Templates fo the given Language
     * @param Database  $db
     * @param array{}[] $templates
     * @param string    $language
     * @param string    $languageName
     * @param integer   $position
     * @return integer
     */
    private static function migrateLanguage(
        Database $db,
        array $templates,
        string $language,
        string $languageName,
        int $position
    ): int {
        $siteName = ConfigCode::getString("name");
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
                "type"         => !empty($template["type"]) ? $template["type"] : "",
                "sendTo"       => !empty($template["sendTo"]) ? "\"{$template["sendTo"]}\"" : "",
                "subject"      => Strings::replace($template["subject"], "[site]", $siteName),
                "message"      => Strings::replace($message, "[site]", $siteName),
                "position"     => $position,
            ];
        }

        // Process the SQL
        if (!empty($updates)) {
            print("<br>Updated <i>" . count($updates) . " emails</i> for language <i>$languageName</i><br>");
            $db->batch("email_templates", $updates);
        }

        return $position;
    }
}
