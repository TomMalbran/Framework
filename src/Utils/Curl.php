<?php
namespace Framework\Utils;

use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Curl Utils
 */
class Curl {

    // The Method Types
    const GET  = "get";
    const POST = "post";
    


    /**
     * Executes the Query
     * @param string $url
     * @param array  $params Optional.
     * @param string $method Optional.
     * @return array
     */
    public static function execute(string $url, array $params = [], string $method = self::GET): array {
        if (Strings::isEqual($method, self::GET)) {
            $content = [];
            foreach ($params as $key => $value) {
                $content[] = $key . "=" . urlencode($value);
            }
            $content = implode("&", $content);
            $url    .= "?" . $content;
        }
        
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FAILONERROR     => 1,
            CURLOPT_TIMEOUT         => 100,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_FORBID_REUSE    => 1,
            CURLOPT_LOW_SPEED_LIMIT => 512,
            CURLOPT_LOW_SPEED_TIME  => 120,
        ];
        if (Strings::isEqual($method, self::POST)) {
            $options += [
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => $params,
            ];
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        if (empty($response)) {
            return [];
        }

        $result = JSON::decode($response, true);
        if (!empty($result["data"])) {
            return $result["data"];
        }
        return $result;
    }
}
