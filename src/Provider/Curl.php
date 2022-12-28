<?php
namespace Framework\Provider;

use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Curl Utils
 */
class Curl {

    /**
     * Executes a GET Request
     * @param string       $url
     * @param array{}|null $params  Optional.
     * @param array{}|null $headers Optional.
     * @return mixed
     */
    public static function get(string $url, ?array $params = null, ?array $headers = null): mixed {
        if (empty($params)) {
            return self::execute($url, $headers);
        }

        $content = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $content[] = "$key=" . JSON::encode($value);
            } else {
                $content[] = "$key=" . urlencode($value);
            }
        }
        $content = Strings::join($content, "&");
        if (!empty($content)) {
            $url .= "?$content";
        }

        return self::execute($url, $headers, null);
    }

    /**
     * Executes a POST Request
     * @param string       $url
     * @param array{}|null $params  Optional.
     * @param array{}|null $headers Optional.
     * @param boolean      $asJson  Optional.
     * @return mixed
     */
    public static function post(string $url, ?array $params = null, ?array $headers = null, bool $asJson = false): mixed {
        $body = $asJson ? json_encode($params) : $params;
        return self::execute($url, $headers, $body);
    }

    /**
     * Executes a Read Request
     * @param string       $url
     * @param string       $filename
     * @param array{}|null $headers  Optional.
     * @return boolean
     */
    public static function read(string $url, string $filename, ?array $headers = null): bool {
        $file    = fopen($filename, "wb");
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_FILE            => $file,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTPGET         => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];
        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        curl_exec($curl);
        curl_close($curl);
        fclose($file);
        return !empty(curl_error($curl));
    }



    /**
     * Executes the Request
     * @param string       $url
     * @param array{}|null $headers Optional.
     * @param mixed|null   $body    Optional.
     * @return mixed
     */
    private static function execute(string $url, ?array $headers = null, mixed $body = null): mixed {
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];

        // Set the Body
        if (!empty($body)) {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        // Set the Header
        if (!empty($headers)) {
            if (is_scalar($body)) {
                $headers["Content-Length"] = strlen($body);
            }
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Execute the Curl
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        if (empty($response)) {
            return [];
        }

        // Remove any possible warning after the response
        $warning = "<br />\n<b>Warning</b>";
        if (Strings::contains($response, $warning)) {
            $response = Strings::substringBefore($response, $warning);
        }

        // Try to decode the response as a JSON
        $result = JSON::decode($response, true);
        if (isset($result["data"])) {
            return $result["data"];
        }
        return $result;
    }

    /**
     * Parses the Header
     * @param array{} $headers
     * @return string[]
     */
    private static function parseHeader(array $headers): array {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }
        return $result;
    }
}
