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
    public function addToken(string $token): void {
        $this->data["jwt"] = $token;
    }

    /**
     * Returns the Data as an Object
     * @return array
     */
    public function toObject(): array {
        return $this->data;
    }

    /**
     * Returns the Data as String
     * @return string
     */
    public function toString(): string {
        return JSON::encode($this->data, true);
    }

    /**
     * Prints the Data
     * @return void
     */
    public function print(): void {
        print($this->toString());
    }

    /**
     * Prints the Data Data
     * @return void
     */
    public function printData(): void {
        if (!empty($this->data["data"])) {
            print(JSON::encode($this->data["data"], true));
        }
    }



    /**
     * Returns an empty result
     * @return Response
     */
    public static function empty(): Response {
        return new Response();
    }

    /**
     * Returns the given result
     * @param array $result Optional.
     * @return Response
     */
    public static function result(array $result = []): Response {
        return new Response($result);
    }

    /**
     * Returns the given data
     * @param array|Model $data Optional.
     * @return Response
     */
    public static function data($data = null): Response {
        return new Response([
            "data" => self::createData($data),
        ]);
    }

    /**
     * Returns an invalid data
     * @return Response
     */
    public static function invalid(): Response {
        return new Response([
            "data" => [ "error" => true ],
        ]);
    }

    /**
     * Returns a logout
     * @return Response
     */
    public static function logout(): Response {
        return new Response([
            "userLoggedOut" => true,
        ]);
    }


    /**
     * Returns a success response
     * @param string      $success
     * @param array|Model $data    Optional.
     * @return Response
     */
    public static function success(string $success, $data = null): Response {
        return new Response([
            "success" => $success,
            "data"    => self::createData($data),
        ]);
    }

    /**
     * Returns a warning response
     * @param string      $warning
     * @param array|Model $data    Optional.
     * @return Response
     */
    public static function warning(string $warning, $data = null): Response {
        return new Response([
            "warning" => $warning,
            "data"    => self::createData($data),
        ]);
    }

    /**
     * Returns an error response
     * @param string|Errros $error
     * @param array|Model   $data  Optional.
     * @return Response
     */
    public static function error($error, $data = null): Response {
        if (is_array($error)) {
            return new Response([
                "errors" => $error,
                "data"   => self::createData($data),
            ]);
        }

        if ($error instanceof Errors) {
            if ($error->has("global")) {
                return new Response([
                    "error" => $error->global,
                    "data"  => self::createData($data),
                ]);
            }
            return new Response([
                "errors" => $error->get(),
                "data"   => self::createData($data),
            ]);
        }

        return new Response([
            "error" => $error,
            "data"  => self::createData($data),
        ]);
    }



    /**
     * Returns the Data depending on the type
     * @param array|Model $data Optional.
     * @return array|null
     */
    private static function createData($data = null) {
        if ($data != null && $data instanceof Model) {
            return $data->toObject();
        }
        return $data;
    }
}
