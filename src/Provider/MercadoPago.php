<?php
namespace Framework\Provider;

use Framework\Config\Config;
use Framework\Utils\DateTime;

/**
 * The MercadoPago Provider
 */
class MercadoPago {

    const BaseUrl = "https://api.mercadopago.com/v1";

    private static bool   $loaded      = false;
    private static string $accessToken = "";
    private static string $signature   = "";


    /**
     * Creates the MercadoPago Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded       = true;
        self::$accessToken = Config::get("mpAccessToken");
        self::$signature   = Config::get("mpSignature");
        return false;
    }

    /**
     * Does a GET Request
     * @param string       $route
     * @param array{}|null $request
     * @param boolean      $asJson
     * @return mixed
     */
    private static function get(string $route, ?array $request, bool $asJson = true): mixed {
        self::load();
        return Curl::get(self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$accessToken,
        ], $asJson);
    }

    /**
     * Does a POST Request
     * @param string  $route
     * @param array{} $request
     * @return mixed
     */
    private static function post(string $route, array $request): mixed {
        self::load();
        return Curl::post(self::BaseUrl . $route, $request, [
            "content-type"  => "application/json",
            "Authorization" => "Bearer " . self::$accessToken,
        ], true);
    }



    /**
     * Validates a Signature
     * @param object $payload
     * @return boolean
     */
    public static function isValidSignature(object $payload): bool {
        self::load();
        $transactionID  = $payload->transaction_id ?: "";
        $generationDate = $payload->generation_date ?: "";
        $signature      = self::$signature ?: "";
        $password       = "$transactionID-$signature-$generationDate";
        $hash           = $payload->signature ?: "";
        $isValid        = password_verify($password, $hash);
        return $isValid;
    }

    /**
     * Downloads a Settlement Report
     * @param string $fileName
     * @return string
     */
    public static function getSettlementReport(string $fileName): string {
        return self::get("/account/settlement_report/$fileName", null, false);
    }

    /**
     * Requests a Settlement Report
     * @param integer $fromTime
     * @param integer $toTime
     * @return array{}
     */
    public static function createSettlementReport(int $fromTime, int $toTime): array {
        return self::post("/account/settlement_report", [
            "begin_date" => DateTime::toUTCString($fromTime),
            "end_date"   => DateTime::toUTCString($toTime),
        ]);
    }
}
