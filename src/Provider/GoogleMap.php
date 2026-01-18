<?php
namespace Framework\Provider;

use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;

/**
 * The Google Map Provider
 */
class GoogleMap {

    private const BaseUrl = "https://maps.google.com/maps/api/";


    /**
     * Returns true if Google Maps is active
     * @return bool
     */
    public static function isActive(): bool {
        return Config::isGoogleMapActive();
    }

    /**
     * Returns an Address and Coordinates from the given Address
     * @param string $address
     * @return array{address:string,latitude:float,longitude:float}|null
     */
    public static function getFromAddress(string $address): array|null {
        return self::getAddress($address, 0, 0);
    }

    /**
     * Returns an Address and Coordinates from the given Latitude and Longitude
     * @param float $latitude
     * @param float $longitude
     * @return array{address:string,latitude:float,longitude:float}|null
     */
    public static function getFromLatLng(float $latitude, float $longitude): array|null {
        return self::getAddress("", $latitude, $longitude);
    }

    /**
     * Returns an Address and Coordinates
     * @param string $address
     * @param float  $latitude
     * @param float  $longitude
     * @return array{address:string,latitude:float,longitude:float}|null
     */
    public static function getAddress(string $address, float $latitude, float $longitude): array|null {
        if (!self::isActive()) {
            return null;
        }

        $params = [ "key" => Config::getGoogleMapApiKey() ];
        if ($latitude !== 0.0 && $longitude !== 0.0) {
            $params["latlng"] = "$latitude,$longitude";
        } elseif ($address !== "") {
            $params["address"] = $address;
        } else {
            return null;
        }

        $url      = self::BaseUrl . "geocode/json";
        $result   = Curl::execute("GET", $url, $params);
        $response = new Dictionary($result);
        $address  = $response->getFirst("results");

        if ($address->isEmpty()) {
            return null;
        }

        foreach ($response->getList("results") as $addressResult) {
            if (Arrays::contains($addressResult->getStrings("types"), "premise")) {
                $address = $addressResult;
                break;
            }
        }

        $location = $address->getDict("geometry")->getDict("location");
        return [
            "address"   => $address->getString("formatted_address"),
            "latitude"  => $location->getFloat("lat"),
            "longitude" => $location->getFloat("lng"),
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
        if (!self::isActive()) {
            return null;
        }

        $params = [
            "key"          => Config::getGoogleMapApiKey(),
            "origins"      => "$latitude1,$longitude1",
            "destinations" => "$latitude2,$longitude2",
            "units"        => "metric",
        ];

        $url      = self::BaseUrl . "distancematrix/json";
        $result   = Curl::execute("GET", $url, $params);
        $response = new Dictionary($result);

        $value    = $response->getFirst("rows")->getFirst("elements")->getDict("distance")->getFloat("value");
        if ($value === 0.0) {
            return null;
        }
        return $value / 1000;
    }
}
