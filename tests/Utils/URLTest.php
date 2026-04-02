<?php
use Framework\Utils\URL;

use PHPUnit\Framework\TestCase;

class URLTest extends TestCase {

    public function testIsValid() {
        $this->assertTrue(URL::isValid("http://example.com"));
        $this->assertTrue(URL::isValid("https://example.com"));
        $this->assertTrue(URL::isValid("https://sub.example.co.uk/path?query=1#frag"));
        $this->assertTrue(URL::isValid("https://example.com:8080"));
        $this->assertTrue(URL::isValid("https://127.0.0.1"));

        // schemes other than http/https are considered invalid by URL
        $this->assertFalse(URL::isValid("ftp://example.com"));

        // missing scheme or plain text is invalid
        $this->assertFalse(URL::isValid("www.example.com"));
        $this->assertFalse(URL::isValid(""));
        $this->assertFalse(URL::isValid("not a url"));
    }

    public function testGetHost() {
        $this->assertEquals("example.com", URL::getHost("http://example.com/path"));
        $this->assertEquals("www.example.co.uk", URL::getHost("https://www.example.co.uk/some/page"));

        // invalid URLs return empty string
        $this->assertEquals("", URL::getHost(""));
        $this->assertEquals("", URL::getHost("not a url"));
        $this->assertEquals("", URL::getHost("example.com/path"));
        $this->assertEquals("", URL::getHost("www.example.com/path"));
    }

    public function testIsValidDomain() {
        $this->assertTrue(URL::isValidDomain("example.com"));
        $this->assertTrue(URL::isValidDomain("sub.example.co.uk"));
        $this->assertTrue(URL::isValidDomain("ex-ample.com"));

        // invalid domains
        $this->assertFalse(URL::isValidDomain("not valid"));
        $this->assertFalse(URL::isValidDomain("example..com"));
        $this->assertFalse(URL::isValidDomain("-example.com"));
        $this->assertFalse(URL::isValidDomain("example.com-"));
        $this->assertFalse(URL::isValidDomain(""));
    }

    public function testGetDomain() {
        $this->assertEquals("example.com", URL::getDomain("Example.Com"));
        $this->assertEquals("example.com", URL::getDomain("www.example.com"));
        $this->assertEquals("example.org", URL::getDomain("WWW.EXAMPLE.ORG"));
        $this->assertEquals("example.com", URL::getDomain("http://www.Example.Com"));
        $this->assertEquals("example.com", URL::getDomain("https://WWW.EXAMPLE.COM"));
        $this->assertEquals("example.com", URL::getDomain("https://WWW.EXAMPLE.COM/path?query=string"));

        // subdomains preserved but www. removed
        $this->assertEquals("sub.example.co.uk", URL::getDomain("Sub.Example.Co.UK"));
        $this->assertEquals("sub.example.co.uk", URL::getDomain("www.Sub.Example.Co.UK"));
        $this->assertEquals("sub.example.co.uk", URL::getDomain("http://www.Sub.Example.Co.UK"));

        // invalid input returns as-is
        $this->assertEquals("not a domain", URL::getDomain("not a domain"));
        $this->assertEquals("", URL::getDomain(""));
    }

    public function testGetDomainExtension() {
        $this->assertEquals("com", URL::getDomainExtension("example.com"));
        $this->assertEquals("uk", URL::getDomainExtension("sub.example.co.uk"));
        $this->assertEquals("uk", URL::getDomainExtension("http://sub.example.co.uk"));
        $this->assertEquals("uk", URL::getDomainExtension("https://sub.example.co.uk"));
        $this->assertEquals("uk", URL::getDomainExtension("https://sub.example.co.uk/path?query=string"));
        $this->assertEquals("localhost", URL::getDomainExtension("localhost"));
        $this->assertEquals("", URL::getDomainExtension(""));
    }

    public function testIsDelegated() {
        $hostIp = gethostbyname("localhost");
        $this->assertTrue(URL::isDelegated("localhost"));

        // when providing the correct server IP it still reports delegated
        $this->assertTrue(URL::isDelegated("localhost", $hostIp));

        // wrong server IP yields false
        $this->assertFalse(URL::isDelegated("localhost", "1.2.3.4"));

        // unknown domain not delegated
        $this->assertFalse(URL::isDelegated("no-such-host-example.invalid"));
    }

    public function testVerifyDelegation() {
        // localhost normally resolves to an IP
        $hostIp = gethostbyname("localhost");
        $this->assertTrue(URL::verifyDelegation("localhost"));
        $this->assertTrue(URL::verifyDelegation("localhost", $hostIp));

        // mismatching server IP should return false
        $this->assertFalse(URL::verifyDelegation("localhost", "1.2.3.4"));

        // non-resolvable domain returns false
        $this->assertFalse(URL::verifyDelegation("no-such-host-example.invalid"));

        // empty input returns false
        $this->assertFalse(URL::verifyDelegation(""));
    }

    public function testIsValidSlug() {
        // basic valid slug
        $this->assertTrue(URL::isValidSlug("a-slug-1"));
        $this->assertTrue(URL::isValidSlug("a"));

        // invalid cases
        $this->assertFalse(URL::isValidSlug(""));
        $this->assertFalse(URL::isValidSlug("a_aslug"));
        $this->assertFalse(URL::isValidSlug("Invalid Slug!"));
    }

    public function testToSlug() {
        $this->assertEquals("a-slug", URL::toSlug("A-Slug"));
        $this->assertEquals("a-slug", URL::toSlug("A Slug!!"));
        $this->assertEquals("a-slug", URL::toSlug("A  Slug !!"));
        $this->assertEquals("simple", URL::toSlug("Simple"));
        $this->assertEquals("", URL::toSlug(""));
    }

    public function testEncode() {
        // spaces are encoded
        $this->assertStringNotContainsString(" ", URL::encode("a b"));
        $this->assertStringContainsString("%20", URL::encode("a b"));

        // complex strings should not contain raw spaces after encoding
        $this->assertStringNotContainsString(" ", URL::encode("a b/c?d=e&f=g"));

        // non-ASCII characters are percent-encoded (UTF-8)
        $this->assertStringContainsString("%C3%B1", URL::encode("mañana"));

        // already-encoded input retains encoding for encoded parts
        $encoded = URL::encode("a%20b");
        $this->assertStringContainsString("%20", $encoded);
    }

    public function testEncodeSpaces() {
        $this->assertEquals("a%20b", URL::encodeSpaces("a b"));
        $this->assertEquals("a%20%20b", URL::encodeSpaces("a  b"));

        // only spaces are encoded
        $this->assertEquals("a", URL::encodeSpaces("a"));
        $this->assertEquals("a/b", URL::encodeSpaces("a/b"));
        $this->assertEquals("a+b", URL::encodeSpaces("a+b"));
    }

    public function testAddParams() {
        $this->assertEquals("/path?a=1&b=2", URL::addParams("/path", [ "a" => 1, "b" => 2 ]));
        $this->assertEquals("/path?existing=1&a=2", URL::addParams("/path?existing=1", [ "a" => 2 ]));

        // values requiring encoding are encoded in the resulting URL
        $result = URL::addParams("/path", [ "q" => "a b" ]);
        $this->assertStringNotContainsString(" ", $result);
        $this->assertStringContainsString("q=", $result);
        $this->assertStringContainsString("+", $result);

        // boolean and numeric values are represented in query string
        $this->assertStringContainsString("flag=true", URL::addParams("/path", [ "flag" => true ]));
        $this->assertStringContainsString("num=0", URL::addParams("/path", [ "num" => 0 ]));
    }

    public function testParseUrlParams() {
        // arrays/objects are JSON-encoded and urlencoded (contains %7B for '{')
        $this->assertStringContainsString("a=%7B", URL::parseParams([ "a" => [ "k" => "v" ]]));

        // special characters are encoded
        $this->assertStringContainsString("+", URL::parseParams([ "q" => "a b" ]));

        // empty input yields empty string
        $this->assertEquals("", URL::parseParams([]));

        // numeric and boolean values serialized as strings
        $this->assertStringContainsString("n=123", URL::parseParams([ "n" => 123 ]));
        $this->assertStringContainsString("t=true", URL::parseParams([ "t" => true ]));
    }

    public function testReplaceInHtml() {
        // images
        $html = "<img src=\"img/pic.jpg\">";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString("http://cdn/", $out);

        // audio tag with src attribute
        $html = "<audio src=\"audio/song.mp3\"></audio>";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString("http://cdn/audio/song.mp3", $out);

        // video tag with src attribute
        $html = "<video src=\"video/clip.mp4\"></video>";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString("http://cdn/video/clip.mp4", $out);

        // do not replace already absolute http/https URLs
        $html = "<img src=\"http://example.com/img.jpg\">";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString("http://example.com/img.jpg", $out);

        $html = "<video src=\"https://videos.example.org/clip.mp4\"></video>";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString("https://videos.example.org/clip.mp4", $out);

        // do not replace data URIs
        $data = "data:image/png;base64,iVBORw0KGgo=";
        $html = "<img src=\"" . $data . "\">";
        $out = URL::replaceInHtml($html, "http://cdn");
        $this->assertStringContainsString($data, $out);
    }
}
