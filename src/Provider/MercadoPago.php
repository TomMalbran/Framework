<?php
namespace Framework\Provider;

use Framework\Provider\Curl;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;

/**
 * The MercadoPago Provider
 */
class MercadoPago {

    const BaseUrl = "https://api.mercadopago.com";
    const AuthUrl = "https://auth.mercadopago.com/authorization";



    /**
     * Does a GET Request
     * @param string       $route
     * @param array{}|null $request      Optional.
     * @param boolean      $jsonResponse Optional.
     * @param string       $accessToken  Optional.
     * @return mixed
     */
    private static function get(string $route, ?array $request = null, bool $jsonResponse = true, string $accessToken = ""): mixed {
        $accessToken = $accessToken ?: Config::getMpAccessToken();
        return Curl::execute("GET", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer $accessToken",
        ], jsonResponse: $jsonResponse);
    }

    /**
     * Does a POST Request
     * @param string  $route
     * @param array{} $request
     * @param array{} $headers     Optional.
     * @param string  $accessToken Optional.
     * @return mixed
     */
    private static function post(string $route, array $request, array $headers = [], string $accessToken = ""): mixed {
        $accessToken = $accessToken ?: Config::getMpAccessToken();
        return Curl::execute("POST", self::BaseUrl . $route, $request, Arrays::merge([
            "content-type"  => "application/json",
            "Authorization" => "Bearer $accessToken",
        ], $headers), jsonBody: true);
    }

    /**
     * Does a PUT Request
     * @param string  $route
     * @param array{} $request
     * @param string  $accessToken Optional.
     * @return mixed
     */
    private static function put(string $route, array $request, string $accessToken = ""): mixed {
        $accessToken = $accessToken ?: Config::getMpAccessToken();
        return Curl::execute("PUT", self::BaseUrl . $route, $request, [
            "content-type"  => "application/json",
            "Authorization" => "Bearer $accessToken",
        ], jsonBody: true);
    }



    /**
     * Returns the Redirect url
     * @param string $redirectUrl
     * @return string
     */
    public static function getRedirectUrl(string $redirectUrl = ""): string {
        if (!empty($redirectUrl)) {
            return $redirectUrl;
        }
        return Config::getUrl(Config::getMpRedirectPath());
    }

    /**
     * Creates an Auth Url
     * @param string $state
     * @param string $redirectUrl Optional.
     * @return string
     */
    public static function getAuthUrl(string $state, string $redirectUrl = ""): string {
        $result = self::AuthUrl;
        $result .= "?response_type=code&platform_id=mp";
        $result .= "&client_id=" . Config::getMpClientId();
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
        $result = self::post("/oauth/token", [
            "client_id"     => Config::getMpClientId(),
            "client_secret" => Config::getMpClientSecret(),
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
        $result = self::post("/oauth/token", [
            "client_id"     => Config::getMpClientId(),
            "client_secret" => Config::getMpClientSecret(),
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
     * @return array{}
     */
    public static function createPaymentUrl(
        string $reference,
        array $items,
        array $payer = [],
        float $marketplaceFee = 0,
        string $accessToken = "",
    ): array {
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

        $notificationPath = Config::getMpNotificationPath();
        if (!empty($notificationPath)) {
            $fields["notification_url"] = Config::getUrl($notificationPath);
        }

        $backUrl = Config::getMpBackUrl();
        if (!empty($backUrl)) {
            $fields["back_urls"] = [
                "success" => $backUrl,
                "pending" => $backUrl,
                "failure" => $backUrl,
            ];
        }

        return self::post("/checkout/preferences", $fields, accessToken: $accessToken);
    }

    /**
     * Cancels a Payment Url
     * @param string $preferenceID
     * @param string $accessToken  Optional.
     * @return boolean
     */
    public static function cancelPaymentUrl(string $preferenceID, string $accessToken = ""): bool {
        $result = self::put("/checkout/preferences/$preferenceID", [
            "expires"            => true,
            "expiration_date_to" => DateTime::format(time(), "Y-m-d\TH:i:s-03:00"),
        ], $accessToken);
        return !empty($result["id"]);
    }

    /**
     * Returns the Payment Data
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return object
     */
    public static function getPaymentData(string $paymentID, string $accessToken = ""): object {
        $result = self::get("/v1/payments/$paymentID", accessToken: $accessToken);
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
        $result = self::put("/v1/payments/$paymentID", [
            "status" => "cancelled",
        ], $accessToken);
        return !empty($result["id"]);
    }

    /**
     * Refunds a Payment
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return boolean
     */
    public static function refundPayment(string $paymentID, string $accessToken = ""): bool {
        $result = self::post("/v1/payments/$paymentID/refunds", [
            "amount" => null,
        ], [
            "X-Idempotency-Key" => Strings::randomCode(20),
        ], $accessToken);
        return !empty($result["id"]);
    }



    /**
     * Validates a Signature
     * @param object $payload
     * @return boolean
     */
    public static function isValidSignature(object $payload): bool {
        $signature      = Config::getMpSignature();
        $transactionID  = $payload->transaction_id ?: "";
        $generationDate = $payload->generation_date ?: "";
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
