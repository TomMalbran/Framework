<?php
namespace Framework\Data;

use Framework\Framework;
use Framework\Request;
use Framework\Config\Config;
use Framework\Schema\Factory;
use Framework\Schema\Database;
use Framework\Schema\Query;

/**
 * The Email Templates
 */
class Email {
    
    private static $loaded = false;
    private static $schema = null;
    
    
    /**
     * Loads the Reset Schema
     * @return Schema
     */
    public static function getSchema() {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("emailTemplates");
        }
        return self::$schema;
    }
    
    

    /**
     * Returns an Email Template with the given Code
     * @param integer $templateCode
     * @return Model
     */
    public static function get($templateCode) {
        $query = Query::create("templateCode", "=", $templateCode);
        return self::getSchema()->getOne($query);
    }
    
    /**
     * Returns true if the given  Email Template exists
     * @param integer $templateCode
     * @return boolean
     */
    public static function exists($templateCode) {
        $query = Query::create("templateCode", "=", $templateCode);
        return self::getSchema()->exists($query);
    }
    
    /**
     * Returns all the Email Templates
     * @param Request $request
     * @return array
     */
    public static function getAll(Request $request) {
        return self::getSchema()->getArray(null, $request);
    }

    /**
     * Returns the total amount of Email Templates
     * @return integer
     */
    public static function getTotal() {
        return self::getSchema()->getTotal();
    }
    
    /**
     * Edits the given Email Template
     * @param integer $templateCode
     * @param Request $request
     * @return void
     */
    public static function edit($templateCode, Request $request) {
        $query = Query::create("templateCode", "=", $templateCode);
        self::getSchema()->edit($query, $request);
    }



    /**
     * Migrates the Emails
     * @param Database $db
     * @param boolean  $recreate Optional.
     * @return void
     */
    public static function migrate(Database $db, $recreate = false) {
        $request  = $db->getAll("email_templates");
        $emails   = Framework::loadData(Framework::EmailData);
        $siteName = Config::get("name");
        $sendAs   = Config::get("smtpEmail");

        $adds     = [];
        $deletes  = [];
        $codes    = [];

        // Adds Emails
        foreach ($emails as $templateCode => $data) {
            $found = false;
            if (!$recreate) {
                foreach ($request as $row) {
                    if ($row["templateCode"] == $templateCode) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $codes[] = $templateCode;
                $adds[]  = [
                    "templateCode" => $templateCode,
                    "description"  => $data["description"],
                    "sendAs"       => $sendAs,
                    "sendName"     => $siteName,
                    "subject"      => str_replace("[site]", $siteName, $data["subject"]),
                    "message"      => str_replace("[site]", $siteName, $data["message"]),
                ];
            }
        }

        // Removes Emails
        foreach ($request as $row) {
            $found = false;
            foreach (array_keys($emails) as $templateCode) {
                if ($row["templateCode"] == $templateCode) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $deletes[] = $templateCode;
            }
        }

        // Process the SQL
        if (!empty($adds)) {
            print("<br>Added <i>" . count($adds) . " emails</i><br>");
            print(implode($codes, ", ") . "<br>");
            $db->batch("email_templates", $adds);
        }
        if (!empty($deletes)) {
            print("<br>Deleted <i>" . count($deletes) . " emails</i><br>");
            print(implode($deletes, ", ") . "<br>");
            foreach ($deletes as $templateCode) {
                $query = Query::create("templateCode", "=", $templateCode);
                $db->delete("email_templates", $query);
            }
        }
        if (empty($adds) && empty($deletes)) {
            print("<br>No <i>emails</i> added or deleted <br>");
        }
    }
}
