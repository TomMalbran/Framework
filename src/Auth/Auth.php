<?php
namespace Framework\Auth;

use Framework\Auth\Access;
use Framework\Auth\Credential;
use Framework\Auth\Token;
use Framework\Auth\Reset;
use Framework\Auth\Spam;
use Framework\Auth\Storage;
use Framework\Config\Config;
use Framework\File\Path;
use Framework\File\File;
use Framework\Log\ActionLog;
use Framework\Provider\JWT;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Status;
use Framework\Utils\Strings;

/**
 * The Auth
 */
class Auth {

    private static $accessLevel  = 0;
    private static $credential   = null;
    private static $credentialID = 0;
    private static $adminID      = 0;
    private static $userID       = 0;
    private static $apiID        = 0;


    /**
     * Validates the Credential
     * @param string  $token
     * @param integer $timezone Optional.
     * @return boolean
     */
    public static function validateCredential(string $token, int $timezone = null): bool {
        Reset::deleteOld();
        Storage::deleteOld();

        if (!JWT::isValid($token)) {
            return false;
        }

        // Retrieve the Token data
        $data       = JWT::getData($token);
        $credential = Credential::getOne($data->credentialID, true);
        if ($credential->isEmpty() || $credential->isDeleted) {
            return false;
        }

        // Set the new Timezone if required
        if (!empty($timezone)) {
            $credentialID = !empty($data->adminID) ? $data->adminID : $data->credentialID;
            Credential::setTimezone($credentialID, $timezone);
        }

        // Set the Credential
        self::setCredential($credential, $data->adminID, $credential->currentUser);

        // Start or reuse a log session
        if (self::isLoggedAsUser()) {
            ActionLog::startSession(self::$adminID);
        } else {
            ActionLog::startSession(self::$credentialID);
        }
        return true;
    }

    /**
     * Validates and Sets the auth as API
     * @param string $token
     * @return boolean
     */
    public static function validateAPI(string $token): bool {
        if (Token::isValid($token)) {
            self::$apiID       = Token::getOne($token)->id;
            self::$accessLevel = Access::API();
            return true;
        }
        return false;
    }

    /**
     * Validates and Sets the auth as API Internal
     * @return boolean
     */
    public static function validateInternal(): bool {
        self::$apiID       = "internal";
        self::$accessLevel = Access::API();
        return true;
    }



    /**
     * Checks the Spam Protection for the Login
     * @return boolean
     */
    public static function spamProtection(): bool {
        return Spam::protect();
    }

    /**
     * Logins the given Credential
     * @param Model $credential
     * @return void
     */
    public static function login(Model $credential): void {
        self::setCredential($credential, 0, $credential->currentUser);

        Credential::updateLoginTime($credential->id);
        ActionLog::startSession($credential->id, true);

        $path = Path::getTempPath($credential->id, false);
        File::emptyDir($path);
        Reset::delete($credential->id);
    }

    /**
     * Logouts the Current Credential
     * @return void
     */
    public static function logout(): void {
        ActionLog::endSession();

        self::$accessLevel  = Access::General();
        self::$credential   = null;
        self::$credentialID = 0;
        self::$adminID      = 0;
        self::$userID       = 0;
        self::$apiID        = 0;
    }

    /**
     * Returns true if the Credential can login
     * @param Model $credential
     * @return boolean
     */
    public static function canLogin(Model $credential): bool {
        return (
            !$credential->isEmpty() &&
            !$credential->isDeleted &&
            !empty($credential->password) &&
            Status::isActive($credential->status)
        );
    }



    /**
     * Logins as the given Credential from an Admin account
     * @param integer $credentialID
     * @return boolean
     */
    public static function loginAs(int $credentialID): bool {
        $admin = self::$credential;
        $user  = Credential::getOne($credentialID, true);

        if (self::canLoginAs($admin, $user)) {
            self::setCredential($user, $admin->id, $user->currentUser);
            return true;
        }
        return false;
    }

    /**
     * Logouts as the current Credential and logins back as the Admin
     * @return integer
     */
    public static function logoutAs(): int {
        if (!self::isLoggedAsUser()) {
            return 0;
        }
        $admin = Credential::getOne(self::$adminID, true);
        $user  = self::$credential;

        if (self::canLoginAs($admin, $user)) {
            self::setCredential($admin);
            return $user->id;
        }
        return 0;
    }

    /**
     * Returns the Credential to Login from the given Email
     * @param string $email
     * @return Model
     */
    public static function getLoginCredential(string $email): Model {
        $parts = Strings::split($email, "|");
        $user  = null;

        if (!empty($parts[0]) && !empty($parts[1])) {
            $admin = Credential::getByEmail($parts[0], true);
            $user  = Credential::getByEmail($parts[1], true);

            if (self::canLoginAs($admin, $user)) {
                $user->password = $admin->password;
                $user->salt     = $admin->salt;
                $user->adminID  = $admin->id;
            }
        } else {
            $user = Credential::getByEmail($email, true);
        }
        return $user;
    }

    /**
     * Returns true if the Admin can login as the User
     * @param Model $admin
     * @param Model $user
     * @return boolean
     */
    public static function canLoginAs(Model $admin, Model $user): bool {
        return (
            self::canLogin($admin) && !$user->isEmpty() && !$user->isDeleted &&
            $admin->level > $user->level && Access::isAdminOrHigher($admin->level)
        );
    }



    /**
     * Sets the Credential
     * @param Model   $credential
     * @param integer $adminID    Optional.
     * @param integer $userID     Optional.
     * @return void
     */
    public static function setCredential(Model $credential, int $adminID = 0, int $userID = 0): void {
        self::$credential   = $credential;
        self::$credentialID = $credential->id;
        self::$accessLevel  = $credential->level;
        self::$adminID      = $adminID;
        self::$userID       = $userID;

        $timezone = !empty($adminID) ? Credential::getOne($adminID)->timezone : $credential->timezone;
        $levels   = Config::getArray("authTimezone");
        if (!empty($timezone) && (empty($levels) || Arrays::contains($levels, $credential->level))) {
            DateTime::setTimezone($timezone);
        }
    }

    /**
     * Sets the Current User
     * @param integer $userID
     * @return void
     */
    public function setCurrentUser(int $userID): void {
        self::$userID = $userID;
        ActionLog::endSession();
        ActionLog::startSession(self::$credentialID, true);
    }

    /**
     * Creates and returns the JWT token
     * @return string
     */
    public static function getToken(): string {
        if (!self::hasCredential()) {
            return "";
        }

        // The general data
        $data = [
            "accessLevel"   => self::$accessLevel,
            "credentialID"  => self::$credentialID,
            "adminID"       => self::$adminID,
            "userID"        => self::$userID,
            "email"         => self::$credential->email,
            "name"          => self::$credential->credentialName,
            "language"      => self::$credential->language,
            "avatar"        => self::$credential->avatar,
            "reqPassChange" => self::$credential->reqPassChange,
            "loggedAsUser"  => !empty(self::$adminID),
        ];

        // Add fields from the Config
        $fields = Config::getArray("authFields");
        foreach ($fields as $field) {
            $data[$field] = self::$credential->get($field);
        }

        return JWT::create(time(), $data);
    }



    /**
     * Returns the Credential Model
     * @return Model
     */
    public static function getCredential(): Model {
        return self::$credential;
    }

    /**
     * Returns the Credential ID
     * @return integer
     */
    public static function getID(): int {
        return self::$credentialID;
    }

    /**
     * Returns the Admin ID
     * @return integer
     */
    public static function getAdminID(): int {
        return self::$adminID;
    }

    /**
     * Returns the Credential Current User
     * @return integer
     */
    public static function getUserID(): int {
        return self::$userID;
    }

    /**
     * Returns the Access Level
     * @return integer
     */
    public static function getAccessLevel(): int {
        return self::$accessLevel;
    }



    /**
     * Returns true if the User is Logged in
     * @return boolean
     */
    public static function isLoggedIn(): bool {
        return !empty(self::$credentialID) || !empty(self::$apiID);
    }

    /**
     * Returns true or false if the admin is logged as an user
     * @return boolean
     */
    public static function isLoggedAsUser(): bool {
        return !empty(self::$adminID);
    }

    /**
     * Returns true if there is a Credential
     * @return boolean
     */
    public static function hasCredential(): bool {
        return !empty(self::$credentialID);
    }



    /**
     * Returns true if the password is correct for the current auth
     * @param string $password
     * @return boolean
     */
    public static function isPasswordCorrect(string $password): bool {
        return Credential::isPasswordCorrect(self::$credentialID, $password);
    }

    /**
     * Returns true if the user has that level
     * @param integer $requested
     * @return boolean
     */
    public static function grant(int $requested): bool {
        return Access::grant(self::$accessLevel, $requested);
    }

    /**
     * Returns true if the user has that level
     * @param integer $requested
     * @return boolean
     */
    public static function requiresLogin(int $requested): bool {
        return !Access::isGeneral($requested) && !self::isLoggedIn();
    }

    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        return Access::$function(self::$accessLevel);
    }
}
