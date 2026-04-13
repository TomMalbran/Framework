<?php
namespace Framework\Intl;

use Framework\Application;
use Framework\Discovery\Discovery;
use Framework\Utils\Dictionary;

/**
 * The Internalization Strings Configuration
 */
class IntlConfig {

    private static string $defaultLanguage  = "en";
    private static string $stringsDir       = "nls/strings";
    private static string $emailsDir        = "nls/emails";
    private static string $notificationsDir = "nls/notifications";



    /**
     * Sets the Default Language
     * @param string $lang
     * @return void
     */
    public static function setDefaultLanguage(string $lang): void {
        self::$defaultLanguage = $lang;
    }

    /**
     * Sets the Strings Directory
     * @param string $dir
     * @return void
     */
    public static function setStringsDir(string $dir): void {
        self::$stringsDir = $dir;
    }

    /**
     * Sets the Emails Directory
     * @param string $dir
     * @return void
     */
    public static function setEmailsDir(string $dir): void {
        self::$emailsDir = $dir;
    }

    /**
     * Sets the Notifications Directory
     * @param string $dir
     * @return void
     */
    public static function setNotificationsDir(string $dir): void {
        self::$notificationsDir = $dir;
    }



    /**
     * Returns the Default Language
     * @return string
     */
    public static function getDefaultLanguage(): string {
        return self::$defaultLanguage;
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
