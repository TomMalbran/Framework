<?php
namespace Framework\Provider;

use Framework\Provider\Curl;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The MercadoPago Provider
 */
class MercadoPago {

    private const BaseUrl = "https://api.mercadopago.com";
    private const AuthUrl = "https://auth.mercadopago.com/authorization";



    /**
     * Does a GET Request
     * @param string       $route
     * @param array{}|null $request      Optional.
     * @param boolean      $jsonResponse Optional.
     * @param string       $accessToken  Optional.
     * @return Dictionary
     */
    private static function get(string $route, ?array $request = null, bool $jsonResponse = true, string $accessToken = ""): Dictionary {
        $accessToken = $accessToken !== "" ? $accessToken : Config::getMpAccessToken();
        $response    = Curl::execute("GET", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer $accessToken",
        ], jsonResponse: $jsonResponse);
        return new Dictionary($response);
    }

    /**
     * Does a POST Request
     * @param string               $route
     * @param array<string,mixed>  $request
     * @param array<string,string> $headers     Optional.
     * @param string               $accessToken Optional.
     * @return Dictionary
     */
    private static function post(string $route, array $request, array $headers = [], string $accessToken = ""): Dictionary {
        $accessToken = $accessToken !== "" ? $accessToken : Config::getMpAccessToken();
        $response    =  Curl::execute("POST", self::BaseUrl . $route, $request, Arrays::merge([
            "content-type"  => "application/json",
            "Authorization" => "Bearer $accessToken",
        ], $headers), jsonBody: true);
        return new Dictionary($response);
    }

    /**
     * Does a PUT Request
     * @param string              $route
     * @param array<string,mixed> $request
     * @param string              $accessToken Optional.
     * @return Dictionary
     */
    private static function put(string $route, array $request, string $accessToken = ""): Dictionary {
        $accessToken = $accessToken !== "" ? $accessToken : Config::getMpAccessToken();
        $response    = Curl::execute("PUT", self::BaseUrl . $route, $request, [
            "content-type"  => "application/json",
            "Authorization" => "Bearer $accessToken",
        ], jsonBody: true);
        return new Dictionary($response);
    }



    /**
     * Returns the Redirect url
     * @param string $redirectUrl
     * @return string
     */
    public static function getRedirectUrl(string $redirectUrl = ""): string {
        if ($redirectUrl !== "") {
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
        $response = self::post("/oauth/token", [
            "client_id"     => Config::getMpClientId(),
            "client_secret" => Config::getMpClientSecret(),
            "code"          => $code,
            "redirect_uri"  => self::getRedirectUrl($redirectUrl),
            "grant_type"    => "authorization_code",
        ]);

        if (!$response->hasValue("user_id")) {
            return (object)[];
        }
        return (object)[
            "userID"         => $response->getString("user_id"),
            "accessToken"    => $response->getString("access_token"),
            "refreshToken"   => $response->getString("refresh_token"),
            "expirationTime" => time() + $response->getInt("expires_in"),
        ];
    }

    /**
     * Recreates a new Access Token
     * @param string $refreshToken
     * @return object
     */
    public static function recreateAccessToken(string $refreshToken): object {
        $response = self::post("/oauth/token", [
            "client_id"     => Config::getMpClientId(),
            "client_secret" => Config::getMpClientSecret(),
            "refresh_token" => $refreshToken,
            "grant_type"    => "refresh_token",
        ]);

        if (!$response->hasValue("user_id")) {
            return (object)[];
        }
        return (object)[
            "userID"         => $response->getString("user_id"),
            "accessToken"    => $response->getString("access_token"),
            "refreshToken"   => $response->getString("refresh_token"),
            "expirationTime" => time() + $response->getInt("expires_in"),
        ];
    }



    /**
     * Creates a Payment Url
     * @param string                $reference
     * @param array<string,mixed>[] $items
     * @param array<string,mixed>   $payer          Optional.
     * @param float                 $marketplaceFee Optional.
     * @param string                $accessToken    Optional.
     * @return array{id:string,url:string}
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
                "id"          => $item["id"],
                "title"       => $item["name"],
                "quantity"    => $item["quantity"],
                "unit_price"  => $item["price"],
                "currency_id" => "ARS",
            ];
        }
        $fields = [
            "external_reference" => $reference,
            "items"              => $itemList,
            "expires"            => false,
        ];

        if ($payer !== []) {
            $fields["payer"] = $payer;
        }
        if ($marketplaceFee !== 0.0) {
            $fields["marketplace_fee"] = $marketplaceFee;
        }

        $notificationPath = Config::getMpNotificationPath();
        if ($notificationPath !== "") {
            $fields["notification_url"] = Config::getUrl($notificationPath);
        }

        $backUrl = Config::getMpBackUrl();
        if ($backUrl !== "") {
            $fields["back_urls"] = [
                "success" => $backUrl,
                "pending" => $backUrl,
                "failure" => $backUrl,
            ];
        }

        $response = self::post("/checkout/preferences", $fields, accessToken: $accessToken);
        return [
            "id"  => $response->getString("id"),
            "url" => $response->getString("init_point"),
        ];
    }

    /**
     * Cancels a Payment Url
     * @param string $preferenceID
     * @param string $accessToken  Optional.
     * @return boolean
     */
    public static function cancelPaymentUrl(string $preferenceID, string $accessToken = ""): bool {
        $response = self::put("/checkout/preferences/$preferenceID", [
            "expires"            => true,
            "expiration_date_to" => DateTime::format(time(), "Y-m-d\TH:i:s-03:00"),
        ], $accessToken);
        return $response->hasValue("id");
    }

    /**
     * Returns the Payment Data
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return object
     */
    public static function getPaymentData(string $paymentID, string $accessToken = ""): object {
        $response = self::get("/v1/payments/$paymentID", accessToken: $accessToken);
        if (!$response->hasValue("id")) {
            return (object)[];
        }

        $mercadoPagoFee = 0;
        $applicationFee = 0;
        foreach ($response->getList("fee_details") as $fee) {
            match ($fee->getString("type")) {
                "mercadopago_fee" => $mercadoPagoFee = $fee->getFloat("amount"),
                "application_fee" => $applicationFee = $fee->getFloat("amount"),
                default           => 0,
            };
        }

        $refund = $response->getFirst("refunds");
        return (object)[
            "id"                => $response->getString("id"),
            "orderID"           => $response->getDict("order")->getString("id"),
            "refundedID"        => $refund->getString("id"),
            "reference"         => $response->getString("external_reference"),
            "status"            => $response->getString("status"),
            "createdTime"       => $response->getTime("date_created"),
            "approvedTime"      => $response->getTime("date_approved"),
            "refundedTime"      => $refund->getTime("date_created"),
            "modifiedTime"      => $response->getTime("date_last_updated"),
            "paymentMethod"     => $response->getString("payment_method_id"),
            "paymentType"       => $response->getString("payment_type_id"),
            "cardInitialNumber" => $response->getString("issuer_id"),
            "cardAuthorization" => $response->getString("authorization_code"),
            "currency"          => $response->getString("currency_id"),
            "installments"      => $response->getInt("installments"),
            "amount"            => $response->getFloat("transaction_amount"),
            "amountRefunded"    => $response->getFloat("transaction_amount_refunded"),
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
        $response = self::put("/v1/payments/$paymentID", [
            "status" => "cancelled",
        ], $accessToken);
        return $response->hasValue("id");
    }

    /**
     * Refunds a Payment
     * @param string $paymentID
     * @param string $accessToken Optional.
     * @return boolean
     */
    public static function refundPayment(string $paymentID, string $accessToken = ""): bool {
        $response = self::post("/v1/payments/$paymentID/refunds", [
            "amount" => null,
        ], [
            "X-Idempotency-Key" => Strings::randomCode(20),
        ], $accessToken);
        return $response->hasValue("id");
    }
}
