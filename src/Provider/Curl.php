<?php
namespace Framework\Provider;

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
     * @return mixed
     */
    public static function execute(string $url, array $params = [], string $method = self::GET) {
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
