<?php
namespace Tests\Utils;

use Framework\Utils\Server;

use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase {

    protected function tearDown(): void {
        // clear any globals we modified
        $_SERVER = [];
        $_REQUEST = [];

        if (isset($GLOBALS["test_getallheaders"])) {
            unset($GLOBALS["test_getallheaders"]);
        }
        if (isset($GLOBALS["test_file_get_contents"])) {
            unset($GLOBALS["test_file_get_contents"]);
        }
    }


    /** @dataProvider providerHas */
    public function testHas(string $key, bool $expected) {
        $_SERVER["SOME_KEY"] = "value";
        $this->assertEquals($expected, Server::has($key));
    }

    public static function providerHas() {
        return [
            "key_exists"     => [ "SOME_KEY", true ],
            "key_not_exists" => [ "NOPE", false ],
        ];
    }


    /** @dataProvider providerGetString */
    public function testGetString(string $key, string $expected) {
        $_SERVER["SOME_KEY"] = "value";
        $this->assertEquals($expected, Server::getString($key));
    }

    public static function providerGetString() {
        return [
            "key_exists"     => [ "SOME_KEY", "value" ],
            "key_not_exists" => [ "NOPE", "" ],
        ];
    }


    /** @dataProvider providerIsPostRequest */
    public function testIsPostRequest(string $method, bool $expected) {
        $_SERVER["REQUEST_METHOD"] = $method;
        $this->assertEquals($expected, Server::isPostRequest());
    }

    public static function providerIsPostRequest() {
        return [
            "no_method"    => [ "", false ],
            "post_request" => [ "POST", true ],
            "get_request"  => [ "GET", false ],
        ];
    }


    /** @dataProvider providerGetAuthToken */
    public function testGetAuthToken(array $serverVars, ?array $headers, string $expected) {
        $_SERVER = $serverVars;
        if ($headers !== null) {
            global $test_getallheaders;
            $test_getallheaders = $headers;
        }
        $this->assertEquals($expected, Server::getAuthToken());
    }

    public static function providerGetAuthToken() {
        return [
            "empty"                => [ [], null, "" ],
            "http_authorization"   => [ [ "HTTP_AUTHORIZATION" => "Bearer xyz789" ], null, "xyz789" ],
            "authorization_header" => [ [ "HTTP_AUTHORIZATION" => "Bearer xyz789" ], [ "Authorization" => "Bearer abc123" ], "abc123" ],
        ];
    }


    /** @dataProvider providerGetPayload */
    public function testGetPayload(array $request, ?string $input, array $expected) {
        $_REQUEST = $request;
        if ($input !== null) {
            global $test_file_get_contents;
            $test_file_get_contents = $input;
        }
        $payload = Server::getPayload();
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $payload->getString($key));
        }
    }

    public static function providerGetPayload() {
        return [
            "request_data" => [ [ "a" => "1", "b" => "2" ], null, [ "a" => "1", "b" => "2" ] ],
            "json_input"   => [ [], '{"x":"y","num":123}', [ "x" => "y", "num" => "123" ] ],
        ];
    }


    /** @dataProvider providerIsLocalHost */
    public function testIsLocalHost(?string $remoteAddr, ?array $allowedIps, bool $expected) {
        if ($remoteAddr !== null) {
            $_SERVER["REMOTE_ADDR"] = $remoteAddr;
        }

        if ($allowedIps === null) {
            $this->assertEquals($expected, Server::isLocalHost());
        } else {
            $this->assertEquals($expected, Server::isLocalHost($allowedIps));
        }
    }

    public static function providerIsLocalHost() {
        return [
            "no_remote_addr"           => [ null, null, false ],
            "localhost_default"        => [ "127.0.0.1", null, true ],
            "localhost_in_allowed"     => [ "127.0.0.1", [ "127.0.0.1" ], true ],
            "localhost_not_in_allowed" => [ "127.0.0.1", [ "1.2.3.4" ], false ],
        ];
    }


    /** @dataProvider providerHostStartsWith */
    public function testHostStartsWith(string $host, string $prefix, bool $expected) {
        if ($host !== "") {
            $_SERVER["HTTP_HOST"] = $host;
        }
        $this->assertEquals($expected, Server::hostStartsWith($prefix));
    }

    public static function providerHostStartsWith() {
        return [
            "no_host"       => [ "", "api.", false ],
            "host_matches"  => [ "api.example.com", "api.", true ],
            "host_no_match" => [ "api.example.com", "www.", false ],
        ];
    }


    /** @dataProvider providerGetUrlAndFullUrl */
    public function testGetUrlAndFullUrl(array $serverVars, bool $useForwarded, string $expectedUrl, string $expectedFullUrl) {
        $_SERVER = $serverVars;
        $this->assertEquals($expectedUrl, Server::getUrl($useForwarded));
        $this->assertEquals($expectedFullUrl, Server::getFullUrl($useForwarded));
    }

    public static function providerGetUrlAndFullUrl() {
        return [
            "empty_server" => [ [], false, "", "" ],
            "http_request" => [
                [ "HTTP_HOST" => "example.com", "SERVER_PROTOCOL" => "HTTP/1.1", "HTTPS" => "off", "SERVER_PORT" => "80", "REQUEST_URI" => "/path?x=1" ],
                false,
                "http://example.com",
                "http://example.com/path?x=1"
            ],
            "forwarded_host" => [
                [ "HTTP_HOST" => "example.com", "SERVER_PROTOCOL" => "HTTP/1.1", "HTTPS" => "off", "SERVER_PORT" => "80", "REQUEST_URI" => "/path?x=1", "HTTP_X_FORWARDED_HOST" => "forwarded.example" ],
                true,
                "http://forwarded.example",
                "http://forwarded.example/path?x=1"
            ],
        ];
    }


    /** @dataProvider providerGetIP */
    public function testGetIP(array $serverVars, ?array $envVars, string $expected) {
        $_SERVER = $serverVars;
        if ($envVars !== null) {
            foreach ($envVars as $key => $value) {
                putenv("$key=$value");
            }
        }
        $this->assertEquals($expected, Server::getIP());
        if ($envVars !== null) {
            foreach ($envVars as $key => $value) {
                putenv($key);
            }
        }
    }

    public static function providerGetIP() {
        return [
            "http_x_forwarded_for"     => [ [ "HTTP_X_FORWARDED_FOR" => "10.0.0.1" ], null, "10.0.0.1" ],
            "http_client_ip"           => [ [ "HTTP_CLIENT_IP" => "192.0.2.4" ], null, "192.0.2.4" ],
            "remote_addr"              => [ [ "REMOTE_ADDR" => "192.0.2.5" ], null, "192.0.2.5" ],
            "env_http_x_forwarded_for" => [ [], [ "HTTP_X_FORWARDED_FOR" => "10.1.1.2" ], "10.1.1.2" ],
            "env_http_client_ip"       => [ [], [ "HTTP_CLIENT_IP" => "192.0.2.9" ], "192.0.2.9" ],
            "env_remote_addr"          => [ [], [ "REMOTE_ADDR" => "192.0.2.10" ], "192.0.2.10" ],
        ];
    }


    /** @dataProvider providerGetUserAgent */
    public function testGetUserAgent(?string $userAgent, string $expected) {
        if ($userAgent !== null) {
            $_SERVER["HTTP_USER_AGENT"] = $userAgent;
        }
        $this->assertEquals($expected, Server::getUserAgent());
    }

    public static function providerGetUserAgent() {
        return [
            "no_user_agent"   => [ null, "" ],
            "with_user_agent" => [ "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/90.0", "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/90.0" ],
        ];
    }


    /** @dataProvider providerGetPlatform */
    public function testGetPlatform(string $ua, string $expected) {
        $this->assertEquals($expected, Server::getPlatform($ua));
    }

    public static function providerGetPlatform() {
        return [
            "empty_ua"      => [ "", "Unknown" ],
            "macos_firefox" => [ "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/88.0", "MacOS FireFox" ],
            "windows_ie"    => [ "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Trident/7.0", "Windows IE" ],
            "iphone_safari" => [ "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) Safari/604.1", "iPhone Safari" ],
            "ipad_chrome"   => [ "Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15 Chrome/90.0", "iPad Chrome" ],
            "android_fluid" => [ "Mozilla/5.0 (Android 10; Mobile; rv:88.0) Gecko/88.0 Fluid/88.0", "Android Fluid" ],
            "windows_air"   => [ "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 AIR/33.0", "Windows Air" ],
            "unknown_agent" => [ "SomeUnknownAgent/1.0", "Unknown" ],
        ];
    }
}
