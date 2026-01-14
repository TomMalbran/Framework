<?php
namespace Framework\Intl;

use Framework\Application;
use Framework\Discovery\Discovery;
use Framework\Utils\Dictionary;

/**
 * The Internalization Strings Configuration
 */
class NLSConfig {

    private static string $stringsDir       = "nls/strings";
    private static string $emailsDir        = "nls/emails";
    private static string $notificationsDir = "nls/notifications";


    /**
     * Sets the Strings Directory
     * @param string $dir
     * @return string
     */
    public static function setStringsDir(string $dir): string {
        self::$stringsDir = $dir;
        return self::$stringsDir;
    }

    /**
     * Sets the Emails Directory
     * @param string $dir
     * @return string
     */
    public static function setEmailsDir(string $dir): string {
        self::$emailsDir = $dir;
        return self::$emailsDir;
    }

    /**
     * Sets the Notifications Directory
     * @param string $dir
     * @return string
     */
    public static function setNotificationsDir(string $dir): string {
        self::$notificationsDir = $dir;
        return self::$notificationsDir;
    }



    /**
     * Returns the path to the Strings Directory
     * @return string
     */
    public static function getStringsPath(): string {
        return Application::getBasePath(self::$stringsDir);
    }



    /**
     * Loads the Strings for the given Language
     * @param string $langCode
     * @return Dictionary
     */
    public static function loadStrings(string $langCode): Dictionary {
        $result = Discovery::loadJSON(self::$stringsDir, $langCode);
        return new Dictionary($result);
    }

    /**
     * Loads the Emails for the given Language
     * @param string $langCode
     * @return Dictionary
     */
    public static function loadEmails(string $langCode): Dictionary {
        $result = Discovery::loadJSON(self::$emailsDir, $langCode);
        return new Dictionary($result);
    }

    /**
     * Loads the Notifications for the given Language
     * @param string $langCode
     * @return Dictionary
     */
    public static function loadNotifications(string $langCode): Dictionary {
        $result = Discovery::loadJSON(self::$notificationsDir, $langCode);
        return new Dictionary($result);
    }
}
