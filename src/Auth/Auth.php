<?php
namespace Framework\Auth;

use Framework\Auth\Access;
use Framework\Auth\Credential;
use Framework\Auth\Token;
use Framework\Auth\Reset;
use Framework\Auth\JWT;
use Framework\File\Path;
use Framework\File\File;
use Framework\Log\ActionLog;
use Framework\Schema\Model;

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
     * @param string $token
     * @return boolean
     */
    public function validateCredential($token) {
        Reset::deleteOld();
        if (!JWT::isValid($token)) {
            return false;
        }
        
        $data       = JWT::getData($token);
        $credential = Credential::getOne($data->credentialID, true);
        if ($credential->isEmpty() || $credential->isDeleted) {
            return false;
        }

        self::setCredential($credential, $data->adminID, $credential->currentUser);

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
    public static function validateAPI($token) {
        if (Token::isValid($token)) {
            self::$apiID       = Token::get($token)->id;
            self::$accessLevel = Access::API();
            return true;
        }
        return false;
    }

    /**
     * Sets the auth as API Internal
     * @return boolean
     */
    public static function validateInternal() {
        self::$apiID       = "internal";
        self::$accessLevel = Access::API();
        return true;
    }



    /**
     * Logins the given Credential
     * @param Model $credential
     * @return void
     */
    public function login(Model $credential) {
        self::setCredential($credential, 0, $credential->currentUser);

        Credential::updateLoginTime($credential->id);
        ActionLog::startSession($credential->id, true);
        
        $path = Path::getTempPath($credential->id, false);
        File::emptyDir($path);
        Reset::delete($credential->id);
    }

    /**
     * Logins as the given Credential from an Admin account
     * @param Model   $credential
     * @param integer $adminID    Optional.
     * @param integer $userID     Optional.
     * @return void
     */
    public function loginAs(Model $credential, $adminID = 0, $userID = 0) {
        self::setCredential($credential, $adminID, $userID);
    }

    /**
     * Logouts the Current Credential
     * @return void
     */
    public function logout() {
        ActionLog::endSession();

        self::$accessLevel  = Access::General();
        self::$credential   = null;
        self::$credentialID = 0;
        self::$adminID      = 0;
        self::$userID       = 0;
        self::$apiID        = 0;
    }


    
    /**
     * Sets the Credential
     * @param Model   $credential
     * @param integer $adminID    Optional.
     * @param integer $userID     Optional.
     * @return void
     */
    public static function setCredential(Model $credential, $adminID = 0, $userID = 0) {
        self::$credential   = $credential;
        self::$credentialID = $credential->id;
        self::$accessLevel  = $credential->level;
        self::$adminID      = $adminID;
        self::$userID       = $userID;
    }

    /**
     * Sets the Current User
     * @param integer $userID
     * @return void
     */
    public function setCurrentUser($userID) {
        self::$userID = $userID;
        ActionLog::endSession();
        ActionLog::startSession(self::$credentialID, true);
    }

    /**
     * Creates and returns the JWT token
     * @return void
     */
    public static function getToken() {
        if (!self::isLoggedIn()) {
            return "";
        }
        return JWT::create(time(), [
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
        ]);
    }



    /**
     * Returns the Credential Model
     * @return Model
     */
    public static function getCredential() {
        return self::$credential;
    }

    /**
     * Returns the Credential ID
     * @return integer
     */
    public static function getID() {
        return self::$credentialID;
    }

    /**
     * Returns the Admin ID
     * @return integer
     */
    public static function getAdminID() {
        return self::$adminID;
    }

    /**
     * Returns the Credential Current User
     * @return integer
     */
    public static function getUserID() {
        return self::$userID;
    }



    /**
     * Returns true if the User is Logged in
     * @return boolean
     */
    public static function isLoggedIn() {
        return !empty(self::$credentialID) || !empty(self::$apiID);
    }
    
    /**
     * Returns true or false if the admin is logged as an user
     * @return boolean
     */
    public static function isLoggedAsUser() {
        return !empty(self::$adminID);
    }



    /**
     * Returns true if the user has that level
     * @param integer $requested
     * @return boolean
     */
    public static function grant($requested) {
        return Access::grant(self::$accessLevel, $requested);
    }

    /**
     * Returns true if the user has that level
     * @param integer $requested
     * @return boolean
     */
    public static function requiresLogin($requested) {
        return !Access::isGeneral($requested) && !self::isLoggedIn();
    }
    
    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic($function, array $arguments) {
        return Access::$function(self::$accessLevel);
    }
}
