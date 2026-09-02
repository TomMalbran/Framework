<?php
namespace Tests\Provider;

use Framework\Provider\Curl;
use Framework\Provider\Type\CurlMethod;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * The Curl Utils
 *
 * A request is made of the Options it is given, and those are built apart
 * from the call that sends them, so every branch is reachable from here
 * without a server to answer. What is left needs one, except for the
 * failures, which an empty url reaches before anything is sent.
 */
class CurlTest extends TestCase {

    /**
     * Returns the Options a Request would be sent with
     * @param array<string,mixed> $args
     * @return array<int,mixed>
     */
    private static function getOptions(array $args): array {
        $method = new ReflectionMethod(Curl::class, "getOptions");
        return (array)$method->invokeArgs(null, $args);
    }



    #[DataProvider("providerGetOptions")]
    public function testGetOptions(array $args, array $expected, array $missing = []): void {
        $options = self::getOptions($args);

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $options);
            $this->assertSame($value, $options[$key]);
        }
        foreach ($missing as $key) {
            $this->assertArrayNotHasKey($key, $options);
        }
    }

    public static function providerGetOptions(): array {
        $url    = "https://api.test/path";
        $params = [ "a" => 1 ];
        $bools  = [ "flag" => true, "off" => false, "a" => 1 ];

        return [
            "a get asks for the params in the url" => [
                [ "method" => CurlMethod::GET, "url" => $url, "params" => $params ],
                [ CURLOPT_URL => "$url?a=1" ],
                [ CURLOPT_POST, CURLOPT_POSTFIELDS, CURLOPT_CUSTOMREQUEST ],
            ],
            "a post sends the params whole"        => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $params ],
                [ CURLOPT_POST => true, CURLOPT_URL => $url, CURLOPT_POSTFIELDS => $params ],
            ],
            "a post with a json body encodes it"   => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $params, "jsonBody" => true ],
                [ CURLOPT_POSTFIELDS => '{"a":1}' ],
            ],
            "a post with a url body parses it"     => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $params, "urlBody" => true ],
                [ CURLOPT_POSTFIELDS => "a=1" ],
            ],
            // The raw body is the one the caller wrote, so nothing else builds one
            "a raw body wins over the others"      => [
                [
                    "method"   => CurlMethod::POST,
                    "url"      => $url,
                    "params"   => $params,
                    "jsonBody" => true,
                    "urlBody"  => true,
                    "rawBody"  => "<xml/>",
                ],
                [ CURLOPT_POSTFIELDS => "<xml/>" ],
            ],
            "a post without params sends no body"  => [
                [ "method" => CurlMethod::POST, "url" => $url ],
                [ CURLOPT_POST => true ],
                [ CURLOPT_POSTFIELDS ],
            ],

            // Curl sends a bool in an array body as a 1 or as nothing at all, and an
            // API that does not read the nothing as a false takes the word instead
            "a post can send the bools as words"   => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $bools, "textBools" => true ],
                [ CURLOPT_POSTFIELDS => [ "flag" => "true", "off" => "false", "a" => 1 ] ],
            ],
            "a post keeps the bools by default"    => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $bools ],
                [ CURLOPT_POSTFIELDS => [ "flag" => true, "off" => false, "a" => 1 ] ],
            ],
            "a json body takes the words too"      => [
                [
                    "method"    => CurlMethod::POST,
                    "url"       => $url,
                    "params"    => $bools,
                    "jsonBody"  => true,
                    "textBools" => true,
                ],
                [ CURLOPT_POSTFIELDS => '{"flag":"true","off":"false","a":1}' ],
            ],
            "a json body keeps them by default"    => [
                [ "method" => CurlMethod::POST, "url" => $url, "params" => $bools, "jsonBody" => true ],
                [ CURLOPT_POSTFIELDS => '{"flag":true,"off":false,"a":1}' ],
            ],
            // A url writes its own bools as words, so there is nothing left to ask for
            "a get writes the words already"       => [
                [ "method" => CurlMethod::GET, "url" => $url, "params" => $bools, "textBools" => true ],
                [ CURLOPT_URL => "$url?flag=true&off=false&a=1" ],
            ],
            "a url body writes them already"       => [
                [
                    "method"    => CurlMethod::POST,
                    "url"       => $url,
                    "params"    => $bools,
                    "urlBody"   => true,
                    "textBools" => true,
                ],
                [ CURLOPT_POSTFIELDS => "flag=true&off=false&a=1" ],
            ],
            "no params leave nothing to change"    => [
                [ "method" => CurlMethod::POST, "url" => $url, "textBools" => true ],
                [ CURLOPT_POST => true ],
                [ CURLOPT_POSTFIELDS ],
            ],

            "another method names itself"          => [
                [ "method" => CurlMethod::PUT, "url" => $url, "params" => $params ],
                [ CURLOPT_CUSTOMREQUEST => "PUT", CURLOPT_URL => "$url?a=1" ],
                [ CURLOPT_POSTFIELDS ],
            ],
            "a custom get is not a get"            => [
                [ "method" => CurlMethod::GET, "url" => $url, "params" => $params, "isCustom" => true ],
                [ CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_URL => "$url?a=1" ],
            ],
            "a custom json body encodes it"        => [
                [ "method" => CurlMethod::PUT, "url" => $url, "params" => $params, "jsonBody" => true ],
                [ CURLOPT_URL => $url, CURLOPT_POSTFIELDS => '{"a":1}' ],
            ],
            "a custom url body parses it"          => [
                [ "method" => CurlMethod::PATCH, "url" => $url, "params" => $params, "urlBody" => true ],
                [ CURLOPT_URL => $url, CURLOPT_POSTFIELDS => "a=1" ],
            ],
            "a custom raw body wins too"           => [
                [
                    "method"   => CurlMethod::DELETE,
                    "url"      => $url,
                    "params"   => $params,
                    "jsonBody" => true,
                    "rawBody"  => "<xml/>",
                ],
                [ CURLOPT_URL => $url, CURLOPT_POSTFIELDS => "<xml/>" ],
            ],

            "the headers become lines"             => [
                [
                    "method"  => CurlMethod::GET,
                    "url"     => $url,
                    "headers" => [ "Authorization" => "Bearer a-token", "Accept" => "application/json" ],
                ],
                [ CURLOPT_HTTPHEADER => [ "Authorization: Bearer a-token", "Accept: application/json" ] ],
            ],
            // A body curl does not build itself has to say how long it is
            "a scalar body measures itself"        => [
                [
                    "method"   => CurlMethod::POST,
                    "url"      => $url,
                    "params"   => $params,
                    "headers"  => [ "Accept" => "application/json" ],
                    "jsonBody" => true,
                ],
                [ CURLOPT_HTTPHEADER => [ "Accept: application/json", "Content-Length: 7" ] ],
            ],
            "an array body measures nothing"       => [
                [
                    "method"  => CurlMethod::POST,
                    "url"     => $url,
                    "params"  => $params,
                    "headers" => [ "Accept" => "application/json" ],
                ],
                [ CURLOPT_HTTPHEADER => [ "Accept: application/json" ] ],
            ],
            "no headers ask for none"              => [
                [ "method" => CurlMethod::GET, "url" => $url, "headers" => [] ],
                [],
                [ CURLOPT_HTTPHEADER ],
            ],

            "the user and password are sent"       => [
                [ "method" => CurlMethod::GET, "url" => $url, "userPass" => "user:pass" ],
                [ CURLOPT_USERPWD => "user:pass" ],
            ],
            "no user asks for none"                => [
                [ "method" => CurlMethod::GET, "url" => $url ],
                [],
                [ CURLOPT_USERPWD ],
            ],
            "the ssl checks can be dropped"        => [
                [ "method" => CurlMethod::GET, "url" => $url, "disableSSL" => true ],
                [ CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false ],
            ],
            "the ssl checks are there by default"  => [
                [ "method" => CurlMethod::GET, "url" => $url ],
                [ CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 100 ],
                [ CURLOPT_SSL_VERIFYPEER, CURLOPT_SSL_VERIFYHOST ],
            ],
            // A redirect is only followed when asked, and never around in a circle
            "the redirects can be followed"        => [
                [ "method" => CurlMethod::GET, "url" => $url, "followUrl" => true ],
                [ CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5 ],
            ],
            "the redirects stay put by default"    => [
                [ "method" => CurlMethod::GET, "url" => $url ],
                [],
                [ CURLOPT_FOLLOWLOCATION, CURLOPT_MAXREDIRS ],
            ],
            "the timeout is the one given"         => [
                [ "method" => CurlMethod::GET, "url" => $url, "timeout" => 5 ],
                [ CURLOPT_TIMEOUT => 5 ],
            ],
        ];
    }


    #[DataProvider("providerExecuteError")]
    public function testExecuteError(array $args, ?string $key): void {
        // An empty url is malformed, so it fails before anything is sent
        $result = Curl::execute(CurlMethod::GET, "", ...$args);
        $error  = $key === null ? $result : $result[$key];

        // The 3 is the code libcurl gives a malformed url, the text is its own
        $this->assertIsString($error);
        $this->assertStringStartsWith("3: ", $error);
    }

    public static function providerExecuteError(): array {
        return [
            "as json"          => [ [ "returnError" => true ], "error" ],
            "as text"          => [ [ "returnError" => true, "jsonResponse" => false ], null ],
            "with the headers" => [ [ "returnError" => true, "withHeaders" => true ], "error" ],
        ];
    }

    public function testTheHeadsComeBackEmpty(): void {
        $result = Curl::execute(CurlMethod::GET, "", returnError: true, withHeaders: true);

        // Nothing was sent, so nothing answered with a header
        $this->assertSame([], $result["headers"]);
    }

    #[DataProvider("providerExecuteQuiet")]
    public function testExecuteQuiet(array $args, mixed $expected): void {
        // Without returnError the failure is the empty answer of the format
        $this->assertSame($expected, Curl::execute(CurlMethod::GET, "", ...$args));
    }

    public static function providerExecuteQuiet(): array {
        return [
            "as json" => [ [], [] ],
            "as text" => [ [ "jsonResponse" => false ], false ],
        ];
    }


    #[DataProvider("providerParseOptions")]
    public function testParseOptions(array $options, array $expected): void {
        $this->assertSame($expected, Curl::parseOptions($options));
    }

    public static function providerParseOptions(): array {
        return [
            "the names of the options" => [
                [ CURLOPT_URL => "https://api.test", CURLOPT_TIMEOUT => 5 ],
                [ "CURLOPT_URL" => "https://api.test", "CURLOPT_TIMEOUT" => 5 ],
            ],
            "one nobody knows"         => [
                [ 999999 => "value" ],
                [ "UNKNOWN_OPTION_999999" => "value" ],
            ],
            "nothing at all"           => [ [], [] ],
        ];
    }
}
