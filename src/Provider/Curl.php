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
     * Executes a Request
     * @param string       $method
     * @param string       $url
     * @param array{}|null $params       Optional.
     * @param array{}|null $headers      Optional.
     * @param string       $userPass     Optional.
     * @param boolean      $jsonBody     Optional.
     * @param boolean      $urlBody      Optional.
     * @param boolean      $jsonResponse Optional.
     * @param boolean      $returnError  Optional.
     * @return mixed
     */
    public static function execute(
        string $method,
        string $url,
        ?array $params = null,
        ?array $headers = null,
        string $userPass = "",
        bool $jsonBody = false,
        bool $urlBody = false,
        bool $jsonResponse = true,
        bool $returnError = false,
    ): mixed {
        return match ($method) {
            "GET"   => self::get($url, $params, $headers, $userPass, $jsonBody, $returnError),
            "POST"  => self::post($url, $params, $headers, $userPass, $jsonBody, $urlBody, $jsonResponse, $returnError),
            default => self::custom($method, $url, $params, $headers, $userPass, $jsonBody, $jsonResponse, $returnError),
        };
    }

    /**
     * Executes a GET Request
     * @param string       $url
     * @param array{}|null $params       Optional.
     * @param array{}|null $headers      Optional.
     * @param string       $userPass     Optional.
     * @param boolean      $jsonResponse Optional.
     * @param boolean      $returnError  Optional.
     * @return mixed
     */
    public static function get(
        string $url,
        ?array $params = null,
        ?array $headers = null,
        string $userPass = "",
        bool $jsonResponse = true,
        bool $returnError = false,
    ): mixed {
        $url = self::parseUrl($url, $params);
        return self::executeRequest("GET", $url, $headers, null, $userPass, $jsonResponse, $returnError);
    }

    /**
     * Executes a POST Request
     * @param string       $url
     * @param array{}|null $params       Optional.
     * @param array{}|null $headers      Optional.
     * @param string       $userPass     Optional.
     * @param boolean      $jsonBody     Optional.
     * @param boolean      $urlBody      Optional.
     * @param boolean      $jsonResponse Optional.
     * @param boolean      $returnError  Optional.
     * @return mixed
     */
    public static function post(
        string $url,
        ?array $params = null,
        ?array $headers = null,
        string $userPass = "",
        bool $jsonBody = false,
        bool $urlBody = false,
        bool $jsonResponse = true,
        bool $returnError = false,
    ): mixed {
        $body = $params;
        if ($jsonBody) {
            $body = json_encode($params);
        } elseif ($urlBody) {
            $body = self::parseParams($params);
        }
        return self::executeRequest("POST", $url, $headers, $body, $userPass, $jsonResponse, $returnError);
    }

    /**
     * Executes a CUSTOM Request
     * @param string       $request
     * @param string       $url
     * @param array{}|null $params       Optional.
     * @param array{}|null $headers      Optional.
     * @param string       $userPass     Optional.
     * @param boolean      $jsonBody     Optional.
     * @param boolean      $jsonResponse Optional.
     * @param boolean      $returnError  Optional.
     * @return mixed
     */
    public static function custom(
        string $request,
        string $url,
        ?array $params = null,
        ?array $headers = null,
        string $userPass = "",
        bool $jsonBody = false,
        bool $jsonResponse = true,
        bool $returnError = false,
    ): mixed {
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
        if ($jsonBody) {
            $options[CURLOPT_POSTFIELDS] = JSON::encode($params);
        } else {
            $options[CURLOPT_URL] = self::parseUrl($url, $params);
        }

        // Set the Headers
        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Set the User and Password
        if (!empty($userPass)) {
            $options[CURLOPT_USERPWD] = $userPass;
        }

        // Execute the Curl
        return self::executeCurl($options, $jsonResponse, $returnError);
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

        // Execute the Curl
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

        // Execute the Curl
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
     * @param string       $method
     * @param string       $url
     * @param array{}|null $headers      Optional.
     * @param mixed|null   $body         Optional.
     * @param string       $userPass     Optional.
     * @param boolean      $jsonResponse Optional.
     * @param boolean      $returnError  Optional.
     * @return mixed
     */
    private static function executeRequest(
        string $method,
        string $url,
        ?array $headers = null,
        mixed $body = null,
        string $userPass = "",
        bool $jsonResponse = true,
        bool $returnError = false,
    ): mixed {
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];

        // Set the Body
        if ($method == "POST") {
            $options[CURLOPT_POST] = true;
            if (!empty($body)) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
        } else {
            $options[CURLOPT_ENCODING] = "identity";
        }

        // Set the Headers
        if (!empty($headers)) {
            if (is_scalar($body)) {
                $headers["Content-Length"] = strlen($body);
            }
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Set the User and Password
        if (!empty($userPass)) {
            $options[CURLOPT_USERPWD] = $userPass;
        }

        // Execute the Curl
        return self::executeCurl($options, $jsonResponse, $returnError);
    }

    /**
     * Executes the CURL
     * @param array{} $options
     * @param boolean $jsonResponse
     * @param boolean $returnError
     * @return mixed
     */
    private static function executeCURL(array $options, bool $jsonResponse, bool $returnError): mixed {
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        // Return the Error
        if ($returnError && $response === false) {
            $error = curl_errno($curl) . ": " . curl_error($curl);
            if (!$jsonResponse) {
                return $error;
            }
            return [ "error" => $error ];
        }

        // Return the Response as a String
        if (!$jsonResponse) {
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
        $content = self::parseParams($params);
        if (!empty($content)) {
            $url .= "?$content";
        }
        return $url;
    }

    /**
     * Parses the Params
     * @param array{}|null $params
     * @return string
     */
    private static function parseParams(?array $params = null): string {
        if (empty($params)) {
            return "";
        }

        $content = [];
        foreach ($params as $key => $value) {
            if (Arrays::isArray($value)) {
                $content[] = "$key=" . urlencode(JSON::encode($value));
            } else {
                $content[] = "$key=" . urlencode($value);
            }
        }

        return Strings::join($content, "&");
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
