<?php
namespace Framework;

use Framework\Schema\Model;
use Framework\Utils\Errors;
use Framework\Utils\JSON;

/**
 * The Response wrapper
 */
class Response {
    
    private $data;

    /**
     * Creates a new Response instance
     * @param array $data Optional.
     */
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Adds a Token
     * @param string $token
     * @return void
     */
    public function addToken($token) {
        $this->data["jwt"] = $token;
    }

    /**
     * Returns the Data as an Object
     * @return array
     */
    public function toObject() {
        return $this->data;
    }

    /**
     * Returns the Data as String
     * @return string
     */
    public function toString() {
        return JSON::encode($this->data, true);
    }
    
    /**
     * Prints the Data
     * @return string
     */
    public function print() {
        return print($this->toString());
    }



    /**
     * Returns an empty result
     * @return array
     */
    public static function empty() {
        return new Response();
    }

    /**
     * Returns the given result
     * @param array $result Optional.
     * @return array
     */
    public static function result(array $result = []) {
        return new Response($result);
    }
    
    /**
     * Returns the given data
     * @param array|Model $data Optional.
     * @return array
     */
    public static function data($data = null) {
        return new Response([
            "data" => self::getData($data),
        ]);
    }

    /**
     * Returns an invalid data
     * @return array
     */
    public static function invalid() {
        return new Response([
            "data" => [ "error" => true ],
        ]);
    }
    
    /**
     * Returns a logout
     * @return array
     */
    public static function logout() {
        return new Response([
            "userLoggedOut" => true,
        ]);
    }


    /**
     * Returns a success response
     * @param string      $success
     * @param array|Model $data    Optional.
     * @return array
     */
    public static function success($success, $data = null) {
        return new Response([
            "success" => $success,
            "data"    => self::getData($data),
        ]);
    }
    
    /**
     * Returns a warning response
     * @param string      $warning
     * @param array|Model $data    Optional.
     * @return array
     */
    public static function warning($warning, $data = null) {
        return new Response([
            "warning" => $warning,
            "data"    => self::getData($data),
        ]);
    }
    
    /**
     * Returns an error response
     * @param string|Errros $error
     * @param array|Model   $data  Optional.
     * @return array
     */
    public static function error($error, $data = null) {
        if ($error instanceof Errors) {
            if ($error->has("global")) {
                return new Response([
                    "error" => $error->global,
                    "data"  => self::getData($data),
                ]);
            }
            return new Response([
                "errors" => $error->get(),
                "data"   => self::getData($data),
            ]);
        }
        return new Response([
            "error" => $error,
            "data"  => self::getData($data),
        ]);
    }



    /**
     * Returns the Data depending on the type
     * @param array|Model $data Optional.
     * @return array|null
     */
    private static function getData($data = null) {
        if ($data != null && $data instanceof Model) {
            return $data->toObject();
        }
        return $data;
    }
}
