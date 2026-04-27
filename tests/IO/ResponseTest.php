<?php
namespace Tests\IO;

use Framework\IO\Errors;
use Framework\IO\Response;
use Framework\IO\Search;
use Framework\Utils\JSON;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use JsonSerializable;

class ResponseTest extends TestCase {

    #[DataProvider("providerAddTokens")]
    public function testAddTokens(bool $withTokens, string $accessToken, string $refreshToken, array $expectedData, array $missingKeys = []) {
        $response = Response::empty($withTokens);
        $response->addTokens($accessToken, $refreshToken);

        $data = $response->toArray();
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertSame($value, $data[$key]);
        }
        foreach ($missingKeys as $key) {
            $this->assertArrayNotHasKey($key, $data);
        }
    }

    public static function providerAddTokens() {
        return [
            "access_and_refresh" => [
                true,
                "a-token",
                "r-token",
                [
                    "xAccessToken"  => "a-token",
                    "xRefreshToken" => "r-token",
                ],
            ],
            "access_only" => [
                true,
                "only-access",
                "",
                [
                    "xAccessToken" => "only-access",
                ],
                [ "xRefreshToken" ],
            ],
            "tokens_disabled" => [
                false,
                "x",
                "y",
                [],
                [ "xAccessToken", "xRefreshToken" ],
            ],
        ];
    }


    #[DataProvider("providerToArray")]
    public function testToArray(array $data, bool $withTokens) {
        $response = new Response($data, $withTokens);
        $this->assertSame($data, $response->toArray());
    }

    public static function providerToArray() {
        return [
            "basic" => [ [ "a" => 1, "b" => "v" ], true ],
            "empty" => [ [], false ],
        ];
    }


    #[DataProvider("providerPrintData")]
    public function testPrintData(Response $response, string $expectedOutput) {
        ob_start();
        $response->printData();
        $output = ob_get_clean();

        $this->assertSame($expectedOutput, $output);
    }

    public static function providerPrintData() {
        $payload = [ "k" => "v", "n" => 3 ];

        return [
            "payload" => [
                Response::data($payload),
                JSON::encode($payload, asPretty: true),
            ],
            "empty_data" => [
                Response::data([]),
                "[]",
            ],
            "missing_data_key" => [
                Response::result([ "x" => 1 ]),
                "",
            ],
        ];
    }


    #[DataProvider("providerEmpty")]
    public function testEmpty(bool $withTokens, ?array $tokens, array $expectedData) {
        $response = Response::empty($withTokens);
        if ($tokens !== null) {
            $response->addTokens($tokens[0], $tokens[1]);
        }

        $this->assertSame($expectedData, $response->toArray());
    }

    public static function providerEmpty() {
        return [
            "default" => [ true, null, [] ],
            "with_tokens" => [
                true,
                [ "a", "b" ],
                [
                    "xAccessToken"  => "a",
                    "xRefreshToken" => "b",
                ],
            ],
            "tokens_disabled" => [ false, [ "a", "b" ], [] ],
        ];
    }


    #[DataProvider("providerExit")]
    public function testExit(int $exitCode) {
        $response = Response::exit($exitCode);

        $this->assertSame([ "result" => $exitCode ], $response->toArray());

        $response->addTokens("a", "b");
        $this->assertSame([ "result" => $exitCode ], $response->toArray());
    }

    public static function providerExit() {
        return [
            "non_zero" => [ 7 ],
            "zero"     => [ 0 ],
        ];
    }


    #[DataProvider("providerResult")]
    public function testResult(array $result) {
        $this->assertSame($result, Response::result($result)->toArray());
    }

    public static function providerResult() {
        return [
            "filled" => [ [ "res" => 1 ] ],
            "empty"  => [ [] ],
        ];
    }


    #[DataProvider("providerData")]
    public function testData(JsonSerializable|array $payload, mixed $expectedData, ?string $expectedClass = null) {
        $data = Response::data($payload)->toArray();

        $this->assertArrayHasKey("data", $data);
        $this->assertSame($expectedData, $data["data"]);
        if ($expectedClass !== null) {
            $this->assertInstanceOf($expectedClass, $data["data"]);
        }
    }

    public static function providerData() {
        $search = new Search(1, "T", null);

        return [
            "array_payload" => [
                [ "x" => 2 ],
                [ "x" => 2 ],
            ],
            "empty_array" => [
                [],
                [],
            ],
            "json_serializable" => [
                $search,
                $search,
                Search::class,
            ],
        ];
    }


    #[DataProvider("providerSearch")]
    public function testSearch(array $searches, array $expectedData, ?string $expectedClass = null) {
        $data = Response::search($searches)->toArray();

        $this->assertArrayHasKey("data", $data);
        $this->assertSame($expectedData, $data["data"]);
        if ($expectedClass !== null && $data["data"] !== []) {
            $this->assertInstanceOf($expectedClass, $data["data"][0]);
        }
    }

    public static function providerSearch() {
        $search = new Search(1, "T", null);

        return [
            "single" => [ [ $search ], [ $search ], Search::class ],
            "empty"  => [ [], [] ],
        ];
    }


    #[DataProvider("providerInvalid")]
    public function testInvalid(array $expectedData) {
        $this->assertSame($expectedData, Response::invalid()->toArray());
    }

    public static function providerInvalid() {
        return [
            "invalid" => [ [ "data" => [ "error" => true ] ] ],
        ];
    }


    #[DataProvider("providerLogout")]
    public function testLogout(array $expectedData) {
        $this->assertSame($expectedData, Response::logout()->toArray());
    }

    public static function providerLogout() {
        return [
            "logout" => [ [ "userLoggedOut" => true ] ],
        ];
    }


    #[DataProvider("providerSuccess")]
    public function testSuccess(string $message, JsonSerializable|array|null $data, string $param, array $expectedData) {
        $this->assertSame($expectedData, Response::success($message, $data, $param)->toArray());
    }

    public static function providerSuccess() {
        return [
            "default_param_and_null_data" => [
                "ok",
                null,
                "",
                [
                    "success" => "ok",
                    "param"   => "",
                    "data"    => null,
                ],
            ],
            "with_data_and_param" => [
                "done",
                [ "x" => 1 ],
                "p",
                [
                    "success" => "done",
                    "param"   => "p",
                    "data"    => [ "x" => 1 ],
                ],
            ],
        ];
    }


    #[DataProvider("providerWarning")]
    public function testWarning(string $message, JsonSerializable|array|null $data, string $param, array $expectedData) {
        $this->assertSame($expectedData, Response::warning($message, $data, $param)->toArray());
    }

    public static function providerWarning() {
        return [
            "default_param_and_null_data" => [
                "be careful",
                null,
                "",
                [
                    "warning" => "be careful",
                    "param"   => "",
                    "data"    => null,
                ],
            ],
            "with_param" => [
                "warn",
                null,
                "pp",
                [
                    "warning" => "warn",
                    "param"   => "pp",
                    "data"    => null,
                ],
            ],
        ];
    }


    #[DataProvider("providerError")]
    public function testError(Errors|string $error, JsonSerializable|array|null $data, array|string $param, array $expectedData) {
        $this->assertSame($expectedData, Response::error($error, $data, $param)->toArray());
    }

    public static function providerError() {
        $errorsWithGlobal = new Errors();
        $errorsWithGlobal->global("global-msg");

        $errorsWithoutGlobal = new Errors();
        $errorsWithoutGlobal->add("f", "m");

        return [
            "string_error_single_param" => [
                "oops",
                null,
                "p1",
                [
                    "error"  => "oops",
                    "param"  => "p1",
                    "params" => [ "p1" ],
                    "data"   => null,
                ],
            ],
            "string_error_multiple_params" => [
                "error",
                null,
                [ "pA", "pB" ],
                [
                    "error"  => "error",
                    "param"  => "pA",
                    "params" => [ "pA", "pB" ],
                    "data"   => null,
                ],
            ],
            "errors_with_global" => [
                $errorsWithGlobal,
                [ "dd" => 1 ],
                [ "pA", "pB" ],
                [
                    "error"  => "global-msg",
                    "param"  => "pA",
                    "params" => [ "pA", "pB" ],
                    "data"   => [ "dd" => 1 ],
                ],
            ],
            "errors_without_global" => [
                $errorsWithoutGlobal,
                null,
                "",
                [
                    "errors" => [ "f" => "m" ],
                    "data"   => null,
                ],
            ],
        ];
    }
}
