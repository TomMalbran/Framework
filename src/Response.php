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

    private bool $withTokens = true;



    /**
     * Creates a new Response instance
     * @param array{} $data       Optional.
     * @param boolean $withTokens Optional.
     */
    public function __construct(array $data = [], bool $withTokens = true) {
        $this->data       = $data;
        $this->withTokens = $withTokens;
    }

    /**
     * Adds the Tokens
     * @param string $jwt
     * @param string $refreshToken
     * @return Response
     */
    public function addTokens(string $jwt, string $refreshToken): Response {
        if (!$this->withTokens) {
            return $this;
        }

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
     * @return Response
     */
    public function print(): Response {
        print($this->toString());
        return $this;
    }

    /**
     * Prints the Data Data
     * @return Response
     */
    public function printData(): Response {
        if (!empty($this->data["data"])) {
            print(JSON::encode($this->data["data"], true));
        }
        return $this;
    }



    /**
     * Returns an empty result
     * @param boolean $withTokens Optional.
     * @return Response
     */
    public static function empty(bool $withTokens = true): Response {
        return new Response(withTokens: $withTokens);
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
     * @param string                   $param   Optional.
     * @return Response
     */
    public static function success(string $success, ArrayAccess|array|null $data = null, string $param = ""): Response {
        return new Response([
            "success" => $success,
            "param"   => $param,
            "data"    => $data,
        ]);
    }

    /**
     * Returns a warning response
     * @param string                   $warning
     * @param ArrayAccess|array{}|null $data    Optional.
     * @param string                   $param   Optional.
     * @return Response
     */
    public static function warning(string $warning, ArrayAccess|array|null $data = null, string $param = ""): Response {
        return new Response([
            "warning" => $warning,
            "param"   => $param,
            "data"    => $data,
        ]);
    }

    /**
     * Returns an error response
     * @param Errors|string            $error
     * @param ArrayAccess|array{}|null $data  Optional.
     * @param string                   $param Optional.
     * @return Response
     */
    public static function error(Errors|string $error, ArrayAccess|array|null $data = null, string $param = ""): Response {
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
                    "param" => $param,
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
            "param" => $param,
            "data"  => $data,
        ]);
    }
}
