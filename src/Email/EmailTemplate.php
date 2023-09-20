<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Config\Config;
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
     * @param Database $db
     * @param boolean  $recreate Optional.
     * @return boolean
     */
    public static function migrate(Database $db, bool $recreate = false): bool {
        if (!$db->hasTable("email_templates")) {
            return false;
        }

        $languages = Language::getAll();
        $oldEmails = $db->getAll("email_templates");

        if ($recreate) {
            print("<br>Removed <i>" . count($oldEmails) . " emails</i><br>");
            $db->truncate("email_templates");
            $oldEmails = [];
        }

        $position = count($oldEmails);
        foreach ($languages as $language => $languageName) {
            $newEmails = Framework::loadJSON(Framework::EmailsDir, $language);
            if (!empty($newEmails)) {
                $position = self::migrateLanguage($db, $oldEmails, $newEmails, $language, $languageName, $position);
            }
        }
        return true;
    }

    /**
     * Migrates the Email Templates fo the given Language
     * @param Database  $db
     * @param array{}[] $oldEmails
     * @param array{}[] $newEmails
     * @param string    $language
     * @param string    $languageName
     * @param integer   $position
     * @return integer
     */
    private static function migrateLanguage(
        Database $db,
        array $oldEmails,
        array $newEmails,
        string $language,
        string $languageName,
        int $position
    ): int {
        $siteName = Config::get("name");
        $sendAs   = Config::get("emailEmail");
        $adds     = [];
        $deletes  = [];
        $codes    = [];

        // Adds the Email Templates
        foreach ($newEmails as $templateCode => $newEmail) {
            $found = false;
            foreach ($oldEmails as $oldEmail) {
                if ($oldEmail["templateCode"] == $templateCode && $oldEmail["language"] == $language) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $position += 1;
                $message   = Strings::join($newEmail["message"], "\n\n");
                $codes[]   = $templateCode;
                $adds[]    = [
                    "templateCode" => $templateCode,
                    "language"     => $language,
                    "languageName" => $languageName,
                    "description"  => $newEmail["description"],
                    "type"         => !empty($newEmail["type"]) ? $newEmail["type"] : "",
                    "sendAs"       => !empty($newEmail["sendAs"]) ? $newEmail["sendAs"] : $sendAs,
                    "sendName"     => $siteName,
                    "sendTo"       => !empty($newEmail["sendTo"]) ? "\"{$newEmail["sendTo"]}\"" : "",
                    "subject"      => Strings::replace($newEmail["subject"], "[site]", $siteName),
                    "message"      => Strings::replace($message, "[site]", $siteName),
                    "position"     => $position,
                ];
            }
        }

        // Removes the Email Templates
        foreach ($oldEmails as $oldEmail) {
            $found = false;
            foreach (array_keys($newEmails) as $templateCode) {
                if ($oldEmail["templateCode"] == $templateCode && $oldEmail) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $deletes[] = $oldEmail["templateCode"];
            }
        }

        // Process the SQL
        if (!empty($adds)) {
            print("<br>Added <i>" . count($adds) . " emails</i><br>");
            print(Strings::join($codes, ", ") . "<br>");
            $db->batch("email_templates", $adds);
        }
        if (!empty($deletes)) {
            print("<br>Deleted <i>" . count($deletes) . " emails</i><br>");
            print(Strings::join($deletes, ", ") . "<br>");
            foreach ($deletes as $templateCode) {
                $query = Query::create("templateCode", "=", $templateCode);
                $db->delete("email_templates", $query);
            }
        }
        if (empty($adds) && empty($deletes)) {
            print("<br>No <i>emails</i> added or deleted <br>");
        }

        return $position;
    }
}
