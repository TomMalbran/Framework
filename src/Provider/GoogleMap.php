<?php
namespace Framework\Provider;

use Framework\System\ConfigCode;
use Framework\Utils\Arrays;

/**
 * The Google Map Provider
 */
class GoogleMap {

    const BaseUrl = "https://maps.google.com/maps/api/";

    private static bool   $loaded   = false;
    private static bool   $isActive = false;
    private static string $apiKey   = "";


    /**
     * Creates the GoogleMap Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded   = true;
        self::$isActive = ConfigCode::getBoolean("googleMapIsActive");
        self::$apiKey   = ConfigCode::getString("googleMapKey");
        return false;
    }



    /**
     * Returns true if Google Maps is active
     * @return boolean
     */
    public static function isActive(): bool {
        self::load();
        return self::$isActive;
    }

    /**
     * Returns an Address and Coordinates from the given Address
     * @param string $address
     * @return array{}
     */
    public static function getFromAddress(string $address): array {
        return self::getAddress($address, 0, 0);
    }

    /**
     * Returns an Address and Coordinates from the given Latitude and Longitude
     * @param float $latitude
     * @param float $longitude
     * @return array{}
     */
    public static function getFromLatLng(float $latitude, float $longitude): array {
        return self::getAddress("", $latitude, $longitude);
    }

    /**
     * Returns an Address and Coordinates
     * @param string $address
     * @param float  $latitude
     * @param float  $longitude
     * @return array{}
     */
    public static function getAddress(string $address, float $latitude, float $longitude): array {
        self::load();
        if (!self::$isActive) {
            return [];
        }

        $params = [ "key" => self::$apiKey ];
        if (!empty($latitude) && !empty($longitude)) {
            $params["latlng"] = "$latitude,$longitude";
        } elseif (!empty($address)) {
            $params["address"] = $address;
        } else {
            return [];
        }

        $url      = self::BaseUrl . "geocode/json";
        $response = Curl::execute("GET", $url, $params);

        if (empty($response["results"][0])) {
            return [];
        }

        $address = $response["results"][0];
        foreach ($response["results"] as $result) {
            if (Arrays::contains($result["types"], "premise")) {
                $address = $result;
                break;
            }
        }

        return [
            "address"   => $address["formatted_address"],
            "latitude"  => $address["geometry"]["location"]["lat"],
            "longitude" => $address["geometry"]["location"]["lng"],
        ];
    }

    /**
     * Calculates the distance between two locations using Google Maps API
     * @param float $latitude1
     * @param float $longitude1
     * @param float $latitude2
     * @param float $longitude2
     * @return float|null
     */
    public static function calculateDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): ?float {
        self::load();
        if (!self::$isActive) {
            return null;
        }

        $params = [
            "key"          => self::$apiKey,
            "origins"      => "$latitude1,$longitude1",
            "destinations" => "$latitude2,$longitude2",
            "units"        => "metric",
        ];

        $url = self::BaseUrl . "distancematrix/json";
        $response = Curl::execute("GET", $url, $params);

        if (empty($response["rows"][0]["elements"][0]["distance"]["value"])) {
            return null;
        }
        return $response["rows"][0]["elements"][0]["distance"]["value"] / 1000;
    }
}
