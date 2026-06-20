<?php
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\URL;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestUrlEnum implements Enum {
    use IsEnum;

    case None;
    case Value;
}

class URLTest extends TestCase {

    #[DataProvider("providerIsValidUrl")]
    public function testIsValid(string $url, bool $expected): void {
        $this->assertEquals($expected, URL::isValid($url));
    }

    public static function providerIsValidUrl(): array {
        return [
            "http_url"         => [ "http://example.com", true ],
            "https_url"        => [ "https://example.com", true ],
            "https_path_query" => [ "https://sub.example.co.uk/path?query=1#frag", true ],
            "https_port"       => [ "https://example.com:8080", true ],
            "ip_address"       => [ "https://127.0.0.1", true ],
            "ftp_protocol"     => [ "ftp://example.com", false ],
            "missing_protocol" => [ "www.example.com", false ],
            "empty_string"     => [ "", false ],
            "invalid_text"     => [ "not a url", false ],
        ];
    }


    #[DataProvider("providerGetHost")]
    public function testGetHost(string $url, string $expected): void {
        $this->assertEquals($expected, URL::getHost($url));
    }

    public static function providerGetHost(): array {
        return [
            "http_url"            => [ "http://example.com/path", "example.com" ],
            "https_url_subdomain" => [ "https://www.example.co.uk/some/page", "www.example.co.uk" ],
            "empty_string"        => [ "", "" ],
            "invalid_url"         => [ "not a url", "" ],
            "missing_protocol_1"  => [ "example.com/path", "" ],
            "missing_protocol_2"  => [ "www.example.com/path", "" ],
        ];
    }


    #[DataProvider("providerIsValidDomain")]
    public function testIsValidDomain(string $domain, bool $expected): void {
        $this->assertEquals($expected, URL::isValidDomain($domain));
    }

    public static function providerIsValidDomain(): array {
        return [
            "valid_domain"            => [ "example.com", true ],
            "valid_subdomain"         => [ "sub.example.co.uk", true ],
            "valid_hyphenated"        => [ "ex-ample.com", true ],
            "invalid_spaces"          => [ "not valid", false ],
            "invalid_double_dot"      => [ "example..com", false ],
            "invalid_leading_hyphen"  => [ "-example.com", false ],
            "invalid_trailing_hyphen" => [ "example.com-", false ],
            "invalid_empty"           => [ "", false ],
        ];
    }


    #[DataProvider("providerGetDomain")]
    public function testGetDomain(string $input, string $expected): void {
        $this->assertEquals($expected, URL::getDomain($input));
    }

    public static function providerGetDomain(): array {
        return [
            "simple_domain"           => [ "Example.Com", "example.com" ],
            "www_prefix"              => [ "www.example.com", "example.com" ],
            "uppercase"               => [ "WWW.EXAMPLE.ORG", "example.org" ],
            "http_www"                => [ "http://www.Example.Com", "example.com" ],
            "https_www"               => [ "https://WWW.EXAMPLE.COM", "example.com" ],
            "https_with_path_query"   => [ "https://WWW.EXAMPLE.COM/path?query=string", "example.com" ],
            "subdomain"               => [ "Sub.Example.Co.UK", "sub.example.co.uk" ],
            "subdomain_with_www"      => [ "www.Sub.Example.Co.UK", "sub.example.co.uk" ],
            "subdomain_http_with_www" => [ "http://www.Sub.Example.Co.UK", "sub.example.co.uk" ],
            "invalid_input"           => [ "not a domain", "not a domain" ],
            "empty_string"            => [ "", "" ],
        ];
    }


    #[DataProvider("providerGetDomainExtension")]
    public function testGetDomainExtension(string $input, string $expected): void {
        $this->assertEquals($expected, URL::getDomainExtension($input));
    }

    public static function providerGetDomainExtension(): array {
        return [
            "simple_domain"         => [ "example.com", "com" ],
            "subdomain_co_uk"       => [ "sub.example.co.uk", "uk" ],
            "http_subdomain_co_uk"  => [ "http://sub.example.co.uk", "uk" ],
            "https_subdomain_co_uk" => [ "https://sub.example.co.uk", "uk" ],
            "https_with_path_query" => [ "https://sub.example.co.uk/path?query=string", "uk" ],
            "localhost"             => [ "localhost", "localhost" ],
            "empty_string"          => [ "", "" ],
        ];
    }


    #[DataProvider("providerIsDelegated")]
    public function testIsDelegated(string $host, string $serverIp, bool $expected): void {
        $this->assertEquals($expected, URL::isDelegated($host, $serverIp));
    }

    public static function providerIsDelegated(): array {
        $hostIp = gethostbyname("localhost");
        return [
            "localhost_no_ip"      => [ "localhost", "", true ],
            "localhost_correct_ip" => [ "localhost", $hostIp, true ],
            "localhost_wrong_ip"   => [ "localhost", "1.2.3.4", false ],
            "invalid_domain"       => [ "no-such-host-example.invalid", "", false ],
        ];
    }


    #[DataProvider("providerVerifyDelegation")]
    public function testVerifyDelegation(string $host, string $serverIp, bool $expected): void {
        $this->assertEquals($expected, URL::verifyDelegation($host, $serverIp));
    }

    public static function providerVerifyDelegation(): array {
        $hostIp = gethostbyname("localhost");
        return [
            "localhost_no_ip"      => [ "localhost", "", true ],
            "localhost_correct_ip" => [ "localhost", $hostIp, true ],
            "localhost_wrong_ip"   => [ "localhost", "1.2.3.4", false ],
            "invalid_domain"       => [ "no-such-host-example.invalid", "", false ],
            "empty_string"         => [ "", "", false ],
        ];
    }


    #[DataProvider("providerIsValidSlug")]
    public function testIsValidSlug(string $slug, bool $expected): void {
        $this->assertEquals($expected, URL::isValidSlug($slug));
    }

    public static function providerIsValidSlug(): array {
        return [
            "basic_slug"             => [ "a-slug-1", true ],
            "single_char"            => [ "a", true ],
            "empty_string"           => [ "", false ],
            "invalid_underscore"     => [ "a_slug", false ],
            "invalid_spaces_special" => [ "Invalid Slug!", false ],
        ];
    }


    #[DataProvider("providerToSlug")]
    public function testToSlug(string $input, string $expected): void {
        $this->assertEquals($expected, URL::toSlug($input));
    }

    public static function providerToSlug(): array {
        return [
            "lowercase_hyphen"   => [ "A-Slug", "a-slug" ],
            "spaces_and_special" => [ "A Slug!!", "a-slug" ],
            "multiple_spaces"    => [ "A  Slug !!", "a-slug" ],
            "simple_word"        => [ "Simple", "simple" ],
            "empty_string"       => [ "", "" ],
        ];
    }


    #[DataProvider("providerEncode")]
    public function testEncode(string $input, string $expectedFragment): void {
        $encoded = URL::encode($input);
        $this->assertStringContainsString($expectedFragment, $encoded);
    }

    public static function providerEncode(): array {
        return [
            "spaces_encoded"           => [ "a b", "%20" ],
            "complex_no_raw_spaces"    => [ "a b/c?d=e&f=g", "%20" ],
            "non_ascii_utf8"           => [ "mañana", "%C3%B1" ],
            "already_encoded_retained" => [ "a%20b", "%20" ],
        ];
    }


    #[DataProvider("providerEncodeSpaces")]
    public function testEncodeSpaces(string $input, string $expected): void {
        $this->assertEquals($expected, URL::encodeSpaces($input));
    }

    public static function providerEncodeSpaces(): array {
        return [
            "spaces_single"   => [ "a b", "a%20b" ],
            "spaces_multiple" => [ "a  b", "a%20%20b" ],
            "no_spaces"       => [ "a", "a" ],
            "forward_slash"   => [ "a/b", "a/b" ],
            "plus_sign"       => [ "a+b", "a+b" ],
        ];
    }


    #[DataProvider("providerDecodeSpaces")]
    public function testDecodeSpaces(string $input, string $expected): void {
        $this->assertEquals($expected, URL::decodeSpaces($input));
    }

    public static function providerDecodeSpaces(): array {
        return [
            "encoded_spaces_single"   => [ "a%20b", "a b" ],
            "encoded_spaces_multiple" => [ "a%20%20b", "a  b" ],
            "no_encoded_spaces"       => [ "a", "a" ],
            "forward_slash"           => [ "a/b", "a/b" ],
            "plus_sign"               => [ "a+b", "a+b" ],
        ];
    }


    #[DataProvider("providerAddParams")]
    public function testAddParams(string $path, array|null $params, string $expected): void {
        $result = URL::addParams($path, $params);
        $this->assertEquals($expected, $result);
    }

    public static function providerAddParams(): array {
        return [
            "basic"    => [ "/path", [ "a" => 1, "b" => 2 ], "/path?a=1&b=2" ],
            "existing" => [ "/path?existing=1", [ "a" => 2 ], "/path?existing=1&a=2" ],
            "spaced"   => [ "/path", [ "q" => "a b" ], "/path?q=a+b" ],
            "boolean"  => [ "/path", [ "flag" => true ], "/path?flag=true" ],
            "numeric"  => [ "/path", [ "num" => 0 ], "/path?num=0" ],
            "enum"     => [ "/path", [ "e" => TestUrlEnum::Value ], "/path?e=Value" ],
            "null_val" => [ "/path", [ "n" => null ], "/path" ],
            "null"     => [ "/path", null, "/path" ],
            "empty"    => [ "/path", [], "/path" ],
        ];
    }


    #[DataProvider("providerParseParams")]
    public function testParseParams(array|null $params, string $expected): void {
        $result = URL::parseParams($params);
        $this->assertEquals($expected, $result);
    }

    public static function providerParseParams(): array {
        return [
            "string"   => [ [ "a" => "1", "b" => "2" ], "a=1&b=2" ],
            "numeric"  => [ [ "n" => 123 ], "n=123" ],
            "boolean"  => [ [ "t" => true ], "t=true" ],
            "array"    => [ [ "a" => [ "k" => "v" ]], "a=%7B%22k%22%3A%22v%22%7D" ],
            "spaced"   => [ [ "q" => "a b" ], "q=a+b" ],
            "enum"     => [ [ "e" => TestUrlEnum::Value ], "e=Value" ],
            "date"     => [ [ "d" => Date::create("2024-01-01") ], "d=2024-01-01+00%3A00%3A00" ],
            "null_val" => [ [ "n" => null, "m" => "12" ], "m=12" ],
            "null"     => [ null, "" ],
            "empty"    => [ [], "" ],
        ];
    }


    #[DataProvider("providerReplaceInHtml")]
    public function testReplaceInHtml(string $html, string $baseUrl, string $expectedFragment): void {
        $out = URL::replaceInHtml($html, $baseUrl);
        $this->assertStringContainsString($expectedFragment, $out);
    }

    public static function providerReplaceInHtml(): array {
        return [
            "image_tag"           => [
                "<img src=\"img/pic.jpg\">",
                "http://cdn",
                "http://cdn/",
            ],
            "audio_tag"           => [
                "<audio src=\"audio/song.mp3\"></audio>",
                "http://cdn",
                "http://cdn/audio/song.mp3",
            ],
            "video_tag"           => [
                "<video src=\"video/clip.mp4\"></video>",
                "http://cdn",
                "http://cdn/video/clip.mp4",
            ],
            "absolute_http_url"   => [
                "<img src=\"http://example.com/img.jpg\">",
                "http://cdn",
                "http://example.com/img.jpg",
            ],
            "absolute_https_url"  => [
                "<video src=\"https://videos.example.org/clip.mp4\"></video>",
                "http://cdn",
                "https://videos.example.org/clip.mp4",
            ],
            "data_uri"            => [
                "<img src=\"data:image/png;base64,iVBORw0KGgo=\">",
                "http://cdn",
                "data:image/png;base64,iVBORw0KGgo=",
            ],
        ];
    }
}
