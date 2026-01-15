<?php
/* code-spell: ignore RETURNTRANSFER, CUSTOMREQUEST, CONNECTTIMEOUT, POSTFIELDS, HTTPHEADER, FOLLOWLOCATION, HTTPGET, USERPWD, HEADERFUNCTION */
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
     * @param string                    $method
     * @param string                    $url
     * @param array<string,mixed>|null  $params       Optional.
     * @param array<string,string>|null $headers      Optional.
     * @param string                    $userPass     Optional.
     * @param boolean                   $isCustom     Optional.
     * @param boolean                   $jsonBody     Optional.
     * @param boolean                   $urlBody      Optional.
     * @param boolean                   $jsonResponse Optional.
     * @param boolean                   $withHeaders  Optional.
     * @param boolean                   $returnError  Optional.
     * @param boolean                   $disableSSL   Optional.
     * @param integer                   $timeout      Optional.
     * @return mixed
     */
    public static function execute(
        string $method,
        string $url,
        ?array $params = null,
        ?array $headers = null,
        string $userPass = "",
        bool $isCustom = false,
        bool $jsonBody = false,
        bool $urlBody = false,
        bool $jsonResponse = true,
        bool $withHeaders = false,
        bool $returnError = false,
        bool $disableSSL = false,
        int $timeout = 100,
    ): mixed {
        $options = [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => $timeout,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];
        if ($disableSSL) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // GET Requests
        if (!$isCustom && $method === "GET") {
            $options[CURLOPT_URL]      = self::parseUrl($url, $params);
            $options[CURLOPT_ENCODING] = "identity";

        // POST Requests
        } elseif (!$isCustom && $method === "POST") {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_URL]  = $url;

            $body = $params;
            if ($jsonBody) {
                $body = JSON::encode($params);
            } elseif ($urlBody) {
                $body = self::parseParams($params);
            }

            if (!Arrays::isEmpty($body)) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
            if ($headers !== null && is_scalar($body)) {
                $headers["Content-Length"] = (string)strlen($body);
            }

        // Custom Requests
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            $options[CURLOPT_ENCODING]      = "identity";

            if ($jsonBody) {
                $options[CURLOPT_URL]        = $url;
                $options[CURLOPT_POSTFIELDS] = JSON::encode($params);
            } else {
                $options[CURLOPT_URL] = self::parseUrl($url, $params);
            }
        }

        // Set the Headers
        if ($headers !== null && count($headers) > 0) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Set the User and Password
        if ($userPass !== "") {
            $options[CURLOPT_USERPWD] = $userPass;
        }

        // Get the Headers
        $headers = [];
        if ($withHeaders) {
            $options[CURLOPT_HEADERFUNCTION] = function($curl, string $header) use (&$headers) {
                $parts = Strings::split($header, ":");
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return Strings::length($header);
            };
        }

        // Execute the Curl
        $curl   = curl_init();
        $result = false;
        if ($curl !== false) {
            curl_setopt_array($curl, $options);
            $result = curl_exec($curl);
            curl_close($curl);
        }


        // Return the Error
        if ($returnError && $result === false) {
            $error = "Cannot initialize cURL";
            if ($curl !== false) {
                $error = curl_errno($curl) . ": " . curl_error($curl);
            }
            if ($withHeaders) {
                return [ "error" => $error, "headers" => $headers ];
            }
            if ($jsonResponse) {
                return [ "error" => $error ];
            }
            return $error;
        }

        // Get the Response
        $response = $result;
        if ($jsonResponse) {
            if ($result === false || $result === "") {
                $response = [];
            } elseif (!JSON::isValid($result)) {
                $response = [ "error" => $result ];
            } else {
                $response = JSON::decodeAsArray($result);
            }
        }

        // Return the Response
        if ($withHeaders) {
            return [ "response" => $response, "headers" => $headers ];
        }
        return $response;
    }

    /**
     * Parses the Url adding the Params
     * @param string                   $url
     * @param array<string,mixed>|null $params
     * @return string
     */
    private static function parseUrl(string $url, ?array $params = null): string {
        $content = self::parseParams($params);
        $prefix  = Strings::contains($url, "?") ? "&" : "?";
        if ($content !== "") {
            $url .= $prefix . $content;
        }
        return $url;
    }

    /**
     * Parses the Params
     * @param array<string,mixed>|null $params
     * @return string
     */
    private static function parseParams(?array $params = null): string {
        if ($params === null || count($params) === 0) {
            return "";
        }

        $content = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $content[] = "$key=" . urlencode(JSON::encode($value));
            } elseif ($value !== null) {
                $content[] = "$key=" . urlencode(Strings::toString($value));
            }
        }

        return Strings::join($content, "&");
    }

    /**
     * Parses the Header
     * @param array<string,string> $headers
     * @return array<integer,string>
     */
    private static function parseHeader(array $headers): array {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }
        return $result;
    }



    /**
     * Executes a Read Request
     * @param string                    $url
     * @param string                    $filePath
     * @param array<string,string>|null $headers  Optional.
     * @return boolean
     */
    public static function read(string $url, string $filePath, ?array $headers = null): bool {
        $file = fopen($filePath, "wb");
        if ($url === "" || $file === false) {
            return false;
        }

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
        if ($headers !== null && count($headers) > 0) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Execute the Curl
        $curl = curl_init();
        if ($curl === false) {
            fclose($file);
            return false;
        }

        curl_setopt_array($curl, $options);
        curl_exec($curl);
        curl_close($curl);
        fclose($file);

        $error = curl_error($curl);
        return $error !== "";
    }

    /**
     * Executes a Write Request
     * @param string                    $url
     * @param string                    $fileContent
     * @param array<string,string>|null $headers     Optional.
     * @return mixed
     */
    public static function write(string $url, string $fileContent, ?array $headers = null): mixed {
        if ($url === "" || $fileContent === "") {
            return [];
        }

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
        if ($headers !== null && count($headers) > 0) {
            $options[CURLOPT_HTTPHEADER] = self::parseHeader($headers);
        }

        // Execute the Curl
        $curl = curl_init();
        if ($curl === false) {
            return [];
        }

        curl_setopt_array($curl, $options);
        curl_exec($curl);
        $output = curl_exec($curl);
        curl_close($curl);

        // Return the Response
        if (!is_string($output)) {
            return [];
        }
        $response = json_decode($output, true);
        return $response;
    }
}
