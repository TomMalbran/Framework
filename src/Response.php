<?php
namespace Framework;

use Framework\Utils\Arrays;
use Framework\Utils\Errors;
use Framework\Utils\JSON;

use ArrayAccess;

/**
 * The Response wrapper
 */
class Response {

    /** @var array{} */
    private array $data;


    /**
     * Creates a new Response instance
     * @param array{} $data Optional.
     */
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Adds the Tokens
     * @param string $jwt
     * @param string $refreshToken
     * @return Response
     */
    public function addTokens(string $jwt, string $refreshToken): Response {
        $this->data["jwt"] = $jwt;
        if (!empty($refreshToken)) {
            $this->data["refreshToken"] = $refreshToken;
        }
        return $this;
    }

    /**
     * Returns the Data as an Object
     * @return array{}
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
     * @param array{} $result Optional.
     * @return Response
     */
    public static function result(array $result = []): Response {
        return new Response($result);
    }

    /**
     * Returns the given data
     * @param ArrayAccess|array{}|null $data Optional.
     * @return Response
     */
    public static function data(ArrayAccess|array|null $data = null): Response {
        return new Response([
            "data" => $data,
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
     * @param string                   $success
     * @param ArrayAccess|array{}|null $data    Optional.
     * @return Response
     */
    public static function success(string $success, ArrayAccess|array|null $data = null): Response {
        return new Response([
            "success" => $success,
            "data"    => $data,
        ]);
    }

    /**
     * Returns a warning response
     * @param string                   $warning
     * @param ArrayAccess|array{}|null $data    Optional.
     * @return Response
     */
    public static function warning(string $warning, ArrayAccess|array|null $data = null): Response {
        return new Response([
            "warning" => $warning,
            "data"    => $data,
        ]);
    }

    /**
     * Returns an error response
     * @param Errors|string            $error
     * @param ArrayAccess|array{}|null $data  Optional.
     * @return Response
     */
    public static function error(Errors|string $error, ArrayAccess|array|null $data = null): Response {
        if (Arrays::isArray($error)) {
            return new Response([
                "errors" => $error,
                "data"   => $data,
            ]);
        }

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
