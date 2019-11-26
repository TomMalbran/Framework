<?php
namespace Framework\Data;

use Framework\Framework;
use Framework\Schema\Model;

/**
 * The Token Data
 */
class Token {
    
    private static $loaded = false;
    private static $data   = [];
    
    
    /**
     * Loads the Tokens Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$data   = Framework::loadData(Framework::TokenData);
        }
    }


    
    /**
     * Returns the Token with the given value
     * @param string $token
     * @return Model
     */
    public function get($token) {
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
     * Returns true if the given Token is valid
     * @param string $token
     * @return boolean
     */
    public function isValid($token) {
        foreach (self::$data as $value) {
            if ($value == $token) {
                return true;
            }
        }
        return false;
    }
}
