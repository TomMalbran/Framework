<?php
namespace Framework;

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
     * @param array $data Optional.
     * @return array
     */
    public static function data(array $data = null) {
        return new Response([
            "data" => $data,
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
     * @param string $success
     * @param array  $data    Optional.
     * @return array
     */
    public static function success($success, array $data = null) {
        return new Response([
            "success" => $success,
            "data"    => $data,
        ]);
    }
    
    /**
     * Returns a warning response
     * @param string $warning
     * @param array  $data    Optional.
     * @return array
     */
    public static function warning($warning, array $data = null) {
        return new Response([
            "warning" => $warning,
            "data"    => $data,
        ]);
    }
    
    /**
     * Returns an error response
     * @param string|Errros $error
     * @param array         $data  Optional.
     * @return array
     */
    public static function error($error, array $data = null) {
        if ($error instanceof Errors) {
            if ($error->has("global")) {
                return new Response([
                    "error" => $error->global,
                    "data"  => $data,
                ]);
            }
            return new Response([
                "errors" => $error->get(),
                "data"   => $data,
            ]);
        }
        return new Response([
            "error" => $error,
            "data"  => $data,
        ]);
    }
}
