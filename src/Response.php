<?php
namespace Framework;

use Framework\Utils\Arrays;
use Framework\Utils\Errors;
use Framework\Utils\Search;
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
     * @param string $accessToken
     * @param string $refreshToken
     * @return Response
     */
    public function addTokens(string $accessToken, string $refreshToken): Response {
        if (!$this->withTokens) {
            return $this;
        }

        $this->data["xAccessToken"] = $accessToken;
        if (!empty($refreshToken)) {
            $this->data["xRefreshToken"] = $refreshToken;
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
     * Returns the given exit code
     * @param integer $exitCode
     * @return Response
     */
    public static function exit(int $exitCode): Response {
        return new Response([ "result" => $exitCode ], false);
    }

    /**
     * Creates a Result Response
     * @param array<string,mixed> $result Optional.
     * @return Response
     */
    public static function result(array $result = []): Response {
        return new Response($result);
    }

    /**
     * Creates a Data Response
     * @param array<string,mixed> $data
     * @return Response
     */
    public static function data(array $data): Response {
        return new Response([
            "data" => $data,
        ]);
    }

    /**
     * Creates a Search Response
     * @param Search[] $data
     * @return Response
     */
    public static function search(array $data): Response {
        return new Response([
            "data" => $data,
        ]);
    }

    /**
     * Creates an Invalid Response
     * @return Response
     */
    public static function invalid(): Response {
        return new Response([
            "data" => [ "error" => true ],
        ]);
    }

    /**
     * Creates a Logout Response
     * @return Response
     */
    public static function logout(): Response {
        return new Response([
            "userLoggedOut" => true,
        ]);
    }



    /**
     * @param string                   $success
     * @param ArrayAccess|array{}|null $data    Optional.
     * @param string                   $param   Optional.
     * Creates a Success Response
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
     * @param string                   $warning
     * @param ArrayAccess|array{}|null $data    Optional.
     * @param string                   $param   Optional.
     * Creates a Warning Response
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
     * @param Errors|string            $error
     * @param ArrayAccess|array{}|null $data  Optional.
     * @param string                   $param Optional.
     * Creates an Error Response
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
