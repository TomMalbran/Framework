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


    public function testHas() {
        $_SERVER["SOME_KEY"] = "value";
        $this->assertTrue(Server::has("SOME_KEY"));
        $this->assertFalse(Server::has("NOPE"));
    }

    public function testGetString() {
        $_SERVER["SOME_KEY"] = "value";
        $this->assertEquals("value", Server::getString("SOME_KEY"));
        $this->assertEquals("", Server::getString("NOPE"));
    }

    public function testIsPostRequest() {
        $this->assertFalse(Server::isPostRequest());

        $_SERVER["REQUEST_METHOD"] = "POST";
        $this->assertTrue(Server::isPostRequest());

        $_SERVER["REQUEST_METHOD"] = "GET";
        $this->assertFalse(Server::isPostRequest());
    }

    public function testGetAuthToken() {
        $this->assertEquals("", Server::getAuthToken());

        // when header missing use HTTP_AUTHORIZATION
        $_SERVER["HTTP_AUTHORIZATION"] = "Bearer xyz789";
        $this->assertEquals("xyz789", Server::getAuthToken());

        // when header present it takes precedence over HTTP_AUTHORIZATION
        global $test_getallheaders;
        $test_getallheaders = [ "Authorization" => "Bearer abc123" ];
        $this->assertEquals("abc123", Server::getAuthToken());
        unset($test_getallheaders);
    }

    public function testGetPayload() {
        // ensure $_REQUEST is used when php://input is empty
        $_REQUEST = [ "a" => "1", "b" => "2" ];
        $payload  = Server::getPayload();
        $this->assertEquals("1", $payload->getString("a"));
        $this->assertEquals("2", $payload->getString("b"));
        $this->assertEquals(2, $payload->getInt("b"));

        // clear $_REQUEST and set php://input so it is chosen
        $_REQUEST = [];
        global $test_file_get_contents;
        $test_file_get_contents = '{"x":"y","num":123}';

        $payload = Server::getPayload();
        $this->assertEquals($test_file_get_contents, $payload->toJSON());
        $this->assertEquals("y", $payload->getString("x"));
        $this->assertEquals(123, $payload->getInt("num"));
    }

    public function testIsLocalHost() {
        $this->assertFalse(Server::isLocalHost());

        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $this->assertTrue(Server::isLocalHost());
        $this->assertTrue(Server::isLocalHost([ "127.0.0.1" ]));
        $this->assertFalse(Server::isLocalHost([ "1.2.3.4" ]));
    }

    public function testHostStartsWith() {
        $this->assertFalse(Server::hostStartsWith("api."));

        $_SERVER["HTTP_HOST"] = "api.example.com";
        $this->assertTrue(Server::hostStartsWith("api."));
        $this->assertFalse(Server::hostStartsWith("www."));
    }

    public function testGetUrlAndFullUrl() {
        $this->assertEquals("", Server::getUrl());
        $this->assertEquals("", Server::getFullUrl());

        // set up typical server variables for a non-HTTPS request
        $_SERVER["HTTP_HOST"]       = "example.com";
        $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
        $_SERVER["HTTPS"]           = "off";
        $_SERVER["SERVER_PORT"]     = "80";
        $_SERVER["REQUEST_URI"]     = "/path?x=1";

        $this->assertEquals("http://example.com", Server::getUrl());
        $this->assertEquals("http://example.com/path?x=1", Server::getFullUrl());

        // forwarded host used when requested
        $_SERVER["HTTP_X_FORWARDED_HOST"] = "forwarded.example";
        $this->assertEquals("http://forwarded.example", Server::getUrl(true));
    }

    public function testGetIP() {
        // prefer HTTP_X_FORWARDED_FOR
        $_SERVER = [ "HTTP_X_FORWARDED_FOR" => "10.0.0.1" ];
        $this->assertEquals("10.0.0.1", Server::getIP());

        // fallback to HTTP_CLIENT_IP
        $_SERVER = [ "HTTP_CLIENT_IP" => "192.0.2.4" ];
        $this->assertEquals("192.0.2.4", Server::getIP());

        // fallback to REMOTE_ADDR
        $_SERVER = [ "REMOTE_ADDR" => "192.0.2.5" ];
        $this->assertEquals("192.0.2.5", Server::getIP());
    }

    public function testGetUserAgent() {
        $this->assertEquals("", Server::getUserAgent());

        $_SERVER["HTTP_USER_AGENT"] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/90.0";
        $this->assertStringContainsString("Mozilla", Server::getUserAgent());
    }

    public function testGetPlatform() {
        $this->assertEquals("Unknown", Server::getPlatform(""));

        // test for Macintosh with Firefox
        $ua = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/88.0";
        $this->assertEquals("MacOS FireFox", Server::getPlatform($ua));

        // test for Windows with IE
        $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Trident/7.0";
        $this->assertEquals("Windows IE", Server::getPlatform($ua));

        // test for iPhone with Safari
        $ua = "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) Safari/604.1";
        $this->assertEquals("iPhone Safari", Server::getPlatform($ua));

        // test iPad with Chrome
        $ua = "Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15 Chrome/90.0";
        $this->assertEquals("iPad Chrome", Server::getPlatform($ua));

        // test Android with Fluid
        $ua = "Mozilla/5.0 (Android 10; Mobile; rv:88.0) Gecko/88.0 Fluid/88.0";
        $this->assertEquals("Android Fluid", Server::getPlatform($ua));

        // test unknown platform
        $ua = "SomeUnknownAgent/1.0";
        $this->assertEquals("Unknown", Server::getPlatform($ua));
    }
}
