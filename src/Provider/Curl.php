<?php
/* code-spell: ignore RETURNTRANSFER, CUSTOMREQUEST, CONNECTTIMEOUT, POSTFIELDS, HTTPHEADER, FOLLOWLOCATION, HTTPGET, USERPWD */
namespace Framework\Provider;

use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Curl Utils
 */
class Curl {

    /**
     * Executes a GET Request
     * @param string       $url
     * @param array{}|null $params   Optional.
     * @param array{}|null $headers  Optional.
     * @param boolean      $asJson   Optional.
     * @param string       $userPass Optional.
     * @return mixed
     */
    public static function get(string $url, ?array $params = null, ?array $headers = null, bool $asJson = true, string $userPass = ""): mixed {
        $url = self::parseUrl($url, $params);
        return self::execute($url, $headers, null, $asJson, $userPass);
    }

    /**
     * Executes a POST Request
     * @param string       $url
     * @param array{}|null $params   Optional.
     * @param array{}|null $headers  Optional.
     * @param boolean      $asJson   Optional.
     * @param string       $userPass Optional.
     * @return mixed
     */
    public static function post(string $url, ?array $params = null, ?array $headers = null, bool $asJson = false, string $userPass = ""): mixed {
        $body = $asJson ? json_encode($params) : $params;
        return self::execute($url, $headers, $body, true, $userPass);
    }

    /**
     * Executes a CUSTOM Request
     * @param string       $request
     * @param string       $url
     * @param array{}|null $params  Optional.
     * @param array{}|null $headers Optional.
     * @param boolean      $asJson  Optional.
     * @return mixed
     */
    public static function custom(string $request, string $url, ?array $params = null, ?array $headers = null, bool $asJson = false): mixed {
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => $request,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
            CURLOPT_ENCODING        => "identity",
        ];

        // Set the Params
        if ($asJson) {
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
        } else {
            $options[CURLOPT_URL] = self::parseUrl($url, $params);
        }

        // Set the Headers
        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $output   = curl_exec($curl);
        $response = json_decode($output, true);
        curl_close($curl);
        return $response;
    }

    /**
     * Executes a Read Request
     * @param string       $url
     * @param string       $filePath
     * @param array{}|null $headers  Optional.
     * @return boolean
     */
    public static function read(string $url, string $filePath, ?array $headers = null): bool {
        $file    = fopen($filePath, "wb");
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_FILE            => $file,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTPGET         => true,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];

        // Set the Headers
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
     * Executes a Write Request
     * @param string       $url
     * @param string       $fileContent
     * @param array{}|null $headers     Optional.
     * @return mixed
     */
    public static function write(string $url, string $fileContent, ?array $headers = null): mixed {
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $fileContent,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];

        // Set the Headers
        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        curl_exec($curl);
        $output   = curl_exec($curl);
        $response = json_decode($output, true);
        curl_close($curl);
        return $response;
    }



    /**
     * Executes the Request
     * @param string       $url
     * @param array{}|null $headers  Optional.
     * @param mixed|null   $body     Optional.
     * @param boolean      $asJson   Optional.
     * @param string       $userPass Optional.
     * @return mixed
     */
    public static function execute(string $url, ?array $headers = null, mixed $body = null, bool $asJson = true, string $userPass = ""): mixed {
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
        } else {
            $options[CURLOPT_ENCODING]   = "identity";
        }

        // Set the Headers
        if (!empty($headers)) {
            if (is_scalar($body)) {
                $headers["Content-Length"] = strlen($body);
            }
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Set the Password
        if (!empty($userPass)) {
            $options[CURLOPT_USERPWD] = $userPass;
        }

        // Execute the Curl
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);

        // Return the Response as a String
        if (!$asJson) {
            return $response;
        }

        // Try to decode the response as a JSON
        if (empty($response)) {
            return [];
        }
        if (!JSON::isValid($response)) {
            return [ "error" => $response ];
        }
        $result = JSON::decode($response, true);
        return $result;
    }

    /**
     * Parses the Url adding the Params
     * @param string       $url
     * @param array{}|null $params
     * @return string
     */
    private static function parseUrl(string $url, ?array $params = null): string {
        if (empty($params)) {
            return $url;
        }

        $content = [];
        foreach ($params as $key => $value) {
            if (Arrays::isArray($value)) {
                $content[] = "$key=" . urlencode(JSON::encode($value));
            } else {
                $content[] = "$key=" . urlencode($value);
            }
        }

        $content = Strings::join($content, "&");
        if (!empty($content)) {
            $url .= "?$content";
        }
        return $url;
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
