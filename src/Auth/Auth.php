<?php
namespace Framework\Auth;

use Framework\Auth\Access;
use Framework\Auth\Token;
use Framework\Schema\Model;
use Framework\Provider\JWT;

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

    private static $time         = 0;
    private static $token        = "";


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
    }

    /**
     * Validates and Sets the auth as API
     * @param string $token
     * @return boolean
     */
    public static function setAPI($token) {
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
    public static function setInternal() {
        self::$apiID       = "internal";
        self::$accessLevel = Access::API();
        return true;
    }

    /**
     * Clears the Data
     * @return void
     */
    public static function clear() {
        self::$accessLevel  = Access::General();
        self::$credential   = null;
        self::$credentialID = 0;
        self::$adminID      = 0;
        self::$userID       = 0;
        self::$apiID        = 0;

        self::$time         = 0;
        self::$token        = "";
    }



    /**
     * Returns true if the given JWT Token is valid
     * @param string $token
     * @return array
     */
    public static function isValidToken($token) {
        return JWT::isValid($token);
    }

    /**
     * Returns the Data from the given JWT Token
     * @param string $token
     * @return array
     */
    public static function getTokenData($token) {
        return JWT::getData($token);
    }

    /**
     * Returns the JWT Token
     * @return string
     */
    public static function getToken() {
        return self::$token;
    }

    /**
     * Creates and Sets the JWT token
     * @param array $data
     * @return void
     */
    public static function setToken(array $data) {
        self::$time  = time();
        self::$token = JWT::create(self::$time, [
            "credentialID" => self::$credentialID,
            "adminID"      => self::$adminID,
            "userID"       => self::$userID,
            "loggedAsUser" => !empty(self::$adminID),
        ] + $data);
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
        return self::$userID;
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
