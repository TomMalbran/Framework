<?php
namespace Framework\Auth;

use Framework\Framework;
use Framework\Schema\Model;
use Framework\Utils\Arrays;

/**
 * The Token Data
 */
class Token {

    private static bool  $loaded = false;
    private static array $data   = [];


    /**
     * Loads the Tokens Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::TokenData);
        return true;
    }



    /**
     * Returns the Token with the given value
     * @param string $token
     * @return Model
     */
    public static function getOne(string $token): Model {
        self::load();

        foreach (self::$data as $tokenID => $value) {
            if ($value == $token) {
                return new Model("tokenID", [
                    "tokenID" => $tokenID,
                    "value"   => $value,
                ]);
            }
        }
        return new Model("tokenID");
    }

    /**
     * Returns the first Token string or the one with the given ID
     * @param integer|null $tokenID Optional.
     * @return string
     */
    public static function getToken(?int $tokenID = null): string {
        self::load();
        if (empty($tokenID)) {
            return Arrays::getFirst(self::$data);
        }
        if (!empty(self::$data[$tokenID])) {
            return self::$data[$tokenID];
        }
        return "";
    }

    /**
     * Returns true if the given Token is valid
     * @param string $token
     * @return boolean
     */
    public static function isValid(string $token): bool {
        self::load();

        foreach (self::$data as $value) {
            if ($value == $token) {
                return true;
            }
        }
        return false;
    }
}
