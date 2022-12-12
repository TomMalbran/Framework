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
     * @param string $url
     * @param array  $params Optional.
     * @return mixed
     */
    public static function get(string $url, array $params = []): mixed {
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

        return self::execute($url);
    }

    /**
     * Executes a POST Request
     * @param string $url
     * @param array  $params
     * @return mixed
     */
    public static function post(string $url, array $params): mixed {
        return self::execute($url, $params);
    }



    /**
     * Executes the Request
     * @param string $url
     * @param array  $params Optional.
     * @return mixed
     */
    private static function execute(string $url, array $params = []): mixed {
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];
        if (!empty($params)) {
            $options += [
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => $params,
            ];
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
}
