<?php
namespace Framework\Auth;

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
    public static function load(): void {
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
    public function getOne(string $token): Model {
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
    public function isValid(string $token): bool {
        foreach (self::$data as $value) {
            if ($value == $token) {
                return true;
            }
        }
        return false;
    }
}
