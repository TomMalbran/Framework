<?php
namespace Framework\Provider;

use Framework\Config\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;

/**
 * The MercadoPago Provider
 */
class MercadoPago {

    const BaseUrl = "https://api.mercadopago.com";
    const AuthUrl = "https://auth.mercadopago.com/authorization";

    private static bool   $loaded          = false;
    private static string $clientID        = "";
    private static string $clientSecret    = "";
    private static string $accessToken     = "";
    private static string $signature       = "";
    private static string $redirectUrl     = "";
    private static string $notificationUrl = "";
    private static string $backUrl         = "";


    /**
     * Creates the MercadoPago Provider
     * @param string $accessToken Optional.
     * @return boolean
     */
    private static function load(string $accessToken = ""): bool {
        if (self::$loaded) {
            return true;
        }

        $config = Config::getObject("mp");

        self::$loaded       = true;
        self::$clientID     = $config->clientId;
        self::$clientSecret = $config->clientSecret;
        self::$accessToken  = $config->accessToken;
        self::$signature    = $config->signature;
        self::$backUrl      = $config->backUrl;

        if (!empty($config->redirectPath)) {
            self::$redirectUrl = Config::getUrl($config->redirectPath);
        }
        if (!empty($config->notificationPath)) {
            self::$notificationUrl = Config::getUrl($config->notificationPath);
        }

        if (!empty($accessToken)) {
            self::$accessToken = $accessToken;
        }
        return false;
    }

    /**
     * Does a GET Request
     * @param string       $route
     * @param array{}|null $request      Optional.
     * @param boolean      $jsonResponse Optional.
     * @return mixed
     */
    private static function get(string $route, ?array $request = null, bool $jsonResponse = true): mixed {
        self::load();
        return Curl::get(self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$accessToken,
        ], jsonResponse: $jsonResponse);
    }

    /**
     * Does a POST Request
     * @param string  $route
     * @param array{} $request
     * @param array{} $headers Optional.
     * @return mixed
     */
    private static function post(string $route, array $request, array $headers = []): mixed {
        self::load();
        return Curl::post(self::BaseUrl . $route, $request, Arrays::merge([
            "content-type"  => "application/json",
            "Authorization" => "Bearer " . self::$accessToken,
        ], $headers), jsonBody: true);
    }

    /**
     * Does a PUT Request
     * @param string  $route
     * @param array{} $request
     * @return mixed
     */
    private static function put(string $route, array $request): mixed {
        self::load();
        return Curl::custom("PUT", self::BaseUrl . $route, $request, [
            "content-type"  => "application/json",
            "Authorization" => "Bearer " . self::$accessToken,
        ], jsonBody: true);
    }



    /**
     * Returns the Redirect url
     * @param string $redirectUrl
     * @return string
     */
    public static function getRedirectUrl(string $redirectUrl = ""): string {
        return !empty($redirectUrl) ? $redirectUrl : self::$redirectUrl;
    }

    /**
     * Creates an Auth Url
     * @param string $state
     * @param string $redirectUrl Optional.
     * @return string
     */
    public static function getAuthUrl(string $state, string $redirectUrl = ""): string {
        self::load();
        $result = self::AuthUrl;
        $result .= "?response_type=code&platform_id=mp";
        $result .= "&client_id=" . self::$clientID;
        $result .= "&state=$state";
        $result .= "&redirect_uri=" . self::getRedirectUrl($redirectUrl);
        return $result;
    }

    /**
     * Creates a new Access Token
     * @param string $code
     * @param string $redirectUrl Optional.
     * @return object
     */
    public static function createAccessToken(string $code, string $redirectUrl = ""): object {
        self::load();
        $result = self::post("/oauth/token", [
            "client_id"     => self::$clientID,
            "client_secret" => self::$clientSecret,
            "code"          => $code,
            "redirect_uri"  => self::getRedirectUrl($redirectUrl),
            "grant_type"    => "authorization_code",
        ]);

        if (empty($result["user_id"])) {
            return (object)[];
        }
        return (object)[
            "userID"         => $result["user_id"],
            "accessToken"    => $result["access_token"],
            "refreshToken"   => $result["refresh_token"],
            "expirationTime" => time() + $result["expires_in"],
        ];
    }

    /**
     * Recreates a new Access Token
     * @param string $refreshToken
     * @return object
     */
    public static function recreateAccessToken(string $refreshToken): object {
        self::load();
        $result = self::post("/oauth/token", [
            "client_id"     => self::$clientID,
            "client_secret" => self::$clientSecret,
            "refresh_token" => $refreshToken,
            "grant_type"    => "refresh_token",
        ]);

        if (empty($result["user_id"])) {
            return (object)[];
        }
        return (object)[
            "userID"         => $result["user_id"],
            "accessToken"    => $result["access_token"],
            "refreshToken"   => $result["refresh_token"],
            "expirationTime" => time() + $result["expires_in"],
        ];
    }



    /**
     * Creates a Payment Url
     * @param string   $reference
     * @param object[] $items
     * @param array{}  $payer          Optional.
     * @param float    $marketplaceFee Optional.
     * @param string   $accessToken    Optional.
     * @return object
     */
    public static function createPaymentUrl(
        string $reference,
        array $items,
        array $payer = [],
        float $marketplaceFee = 0,
        string $accessToken = "",
    ): object {
        self::load($accessToken);

        $itemList = [];
        foreach ($items as $item) {
            $itemList[] = [
                "id"          => $item->id,
                "title"       => $item->name,
                "quantity"    => $item->quantity,
                "currency_id" => "ARS",
                "unit_price"  => $item->price,
            ];
        }
        $fields = [
            "external_reference" => $reference,
            "items"              => $itemList,
            "expires"            => false,
        ];

        if (!empty($payer)) {
            $fields["payer"] = $payer;
        }
        if (!empty($marketplaceFee)) {
            $fields["marketplace_fee"] = $marketplaceFee;
        }
        if (!empty(self::$notificationUrl)) {
            $fields["notification_url"] = self::$notificationUrl;
        }
        if (!empty(self::$backUrl)) {
            $fields["back_urls"] = [
                "success" => self::$backUrl,
                "pending" => self::$backUrl,
                "failure" => self::$backUrl,
            ];
        }

        $result = self::post("/checkout/preferences", $fields);
        if (empty($result["id"])) {
            return (object)[];
        }

        return (object)[
            "id"  => $result["id"],
            "url" => $result["init_point"],
        ];
    }

    /**
     * Cancels a Payment Url
     * @param string $preferenceID
     * @param string $accessToken  Optional.
     * @return boolean
     */
    public static function cancelPaymentUrl(string $preferenceID, string $accessToken = ""): bool {
        self::load($accessToken);

        $result = self::put("/checkout/preferences/$preferenceID", [
            "expires"            => true,
            "expiration_date_to" => DateTime::format(time(), "Y-m-d\TH:i:s-03:00"),
        ]);
        return !empty($result["id"]);
    }

    /**
     * Returns the Payment Data
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return object
     */
    public static function getPaymentData(string $paymentID, string $accessToken = ""): object {
        self::load($accessToken);

        $result = self::get("/v1/payments/$paymentID");
        if (empty($result["id"])) {
            return (object)[];
        }

        $mercadoPagoFee = 0;
        $applicationFee = 0;
        foreach ($result["fee_details"] as $fee) {
            match ($fee["type"]) {
                "mercadopago_fee" => $mercadoPagoFee = $fee["amount"],
                "application_fee" => $applicationFee = $fee["amount"],
                default           => 0,
            };
        }

        $refundedID   = 0;
        $refundedTime = 0;
        if (!empty($result["refunds"][0])) {
            $refundedID   = $result["refunds"][0]["id"];
            $refundedTime = DateTime::toTime($result["refunds"][0]["date_created"]);
        }

        return (object)[
            "id"                => $result["id"],
            "orderID"           => $result["order"]["id"],
            "refundedID"        => $refundedID,
            "reference"         => $result["external_reference"],
            "status"            => $result["status"],
            "createdTime"       => DateTime::toTime($result["date_created"]),
            "approvedTime"      => DateTime::toTime($result["date_approved"]),
            "refundedTime"      => $refundedTime,
            "modifiedTime"      => DateTime::toTime($result["date_last_updated"]),
            "paymentMethod"     => $result["payment_method_id"],
            "paymentType"       => $result["payment_type_id"],
            "cardInitialNumber" => !empty($result["issuer_id"]) ? $result["issuer_id"] : "",
            "cardAuthorization" => !empty($result["authorization_code"]) ? $result["authorization_code"] : "",
            "currency"          => $result["currency_id"],
            "installments"      => $result["installments"],
            "amount"            => $result["transaction_amount"],
            "amountRefunded"    => $result["transaction_amount_refunded"],
            "mercadoPagoFee"    => $mercadoPagoFee,
            "applicationFee"    => $applicationFee,
        ];
    }

    /**
     * Cancels a Payment
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return boolean
     */
    public static function cancelPayment(string $paymentID, string $accessToken = ""): bool {
        self::load($accessToken);

        $result = self::put("/v1/payments/$paymentID", [
            "status" => "cancelled",
        ]);
        return !empty($result["id"]);
    }

    /**
     * Refunds a Payment
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return boolean
     */
    public static function refundPayment(string $paymentID, string $accessToken = ""): bool {
        self::load($accessToken);

        $result = self::post("/v1/payments/$paymentID/refunds", [
            "amount" => null,
        ], [
            "X-Idempotency-Key" => Strings::randomCode(20),
        ]);
        return !empty($result["id"]);
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
        return self::get("/v1/account/settlement_report/$fileName", null, false);
    }

    /**
     * Requests a Settlement Report
     * @param integer $fromTime
     * @param integer $toTime
     * @return array{}
     */
    public static function createSettlementReport(int $fromTime, int $toTime): array {
        return self::post("/v1/account/settlement_report", [
            "begin_date" => DateTime::toUTCString($fromTime),
            "end_date"   => DateTime::toUTCString($toTime),
        ]);
    }
}
