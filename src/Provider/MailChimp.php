<?php
// spell-checker: ignore  apikey
namespace Framework\Provider;

use Framework\Email\EmailWhiteList;
use Framework\System\Config;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The MailChimp Provider
 */
class MailChimp {

    private const BaseUrl = "https://<dc>.api.mailchimp.com/3.0";


    /**
     * Returns the Base Url
     * @return string
     */
    private static function getUrl(string $route): string {
        $key     = Config::getMailchimpKey();
        $dc      = Strings::substringAfter($key, "-");
        $baseUrl = Strings::replace(self::BaseUrl, "<dc>", $dc);
        return "$baseUrl/$route";
    }

    /**
     * Returns the Headers
     * @return array<string,string>
     */
    private static function getHeaders(): array {
        $key = Config::getMailchimpKey();
        return [
            "Accept"        => "application/vnd.api+json",
            "Content-Type"  => "application/vnd.api+json",
            "Authorization" => "apikey $key",
        ];
    }

    /**
     * Returns true if the result is successful
     * @param array<string|integer,mixed> $result
     * @return boolean
     */
    private static function isSuccess(array $result): bool {
        if (isset($result["status"])) {
            $status = (int)$result["status"];
            return $status >= 200 && $status <= 299;
        }
        return false;
    }

    /**
     * Does a GET Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return array<string|integer,mixed>
     */
    private static function get(string $route, ?array $request = null): array {
        $result = Curl::execute("GET", self::getUrl($route), $request, self::getHeaders());
        return is_array($result) ? $result : [];
    }

    /**
     * Does a POST Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return array<string|integer,mixed>
     */
    private static function post(string $route, ?array $request = null): array {
        $result = Curl::execute("POST", self::getUrl($route), $request, self::getHeaders(), jsonBody: true);
        return is_array($result) ? $result : [];
    }

    /**
     * Does a PATCH Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return array<string|integer,mixed>
     */
    private static function patch(string $route, ?array $request = null): array {
        $result = Curl::execute("PATCH", self::getUrl($route), $request, self::getHeaders(), jsonBody: true);
        return is_array($result) ? $result : [];
    }

    /**
     * Does a PUT Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return array<string|integer,mixed>
     */
    private static function put(string $route, ?array $request = null): array {
        $result = Curl::execute("PUT", self::getUrl($route), $request, self::getHeaders(), jsonBody: true);
        return is_array($result) ? $result : [];
    }

    /**
     * Does a DELETE Request
     * @param string $route
     * @return array<string|integer,mixed>
     */
    private static function delete(string $route): array {
        $result = Curl::execute("DELETE", self::getUrl($route), null, self::getHeaders());
        return is_array($result) ? $result : [];
    }



    /**
     * Convert an email address into a subscriber hash
     * @param string $email
     * @return string
     */
    public static function getSubscriberHash(string $email): string {
        return md5(strtolower($email));
    }

    /**
     * Returns the Members Route
     * @param string $route Optional.
     * @return string
     */
    private static function getSubscribersRoute(string $route = ""): string {
        $listID = Config::getMailchimpList();
        $result = "lists/$listID/members";
        if (!empty($route)) {
            $result .= "/$route";
        }
        return $result;
    }

    /**
     * Returns all the subscribers
     * @param integer $count  Optional.
     * @param integer $offset Optional.
     * @return mixed[]
     */
    public static function getAllSubscribers(int $count = 100, int $offset = 0): array {
        if (!Config::isMailchimpActive()) {
            return [];
        }

        $route    = self::getSubscribersRoute();
        $response = self::get($route, [
            "count"  => $count,
            "offset" => $offset,
        ]);
        return $response;
    }

    /**
     * Returns the Subscriber with the given email
     * @param string $email
     * @return mixed[]|mixed|null
     */
    public static function getSubscriber(string $email): mixed {
        if (!Config::isMailchimpActive()) {
            return null;
        }

        $hash     = self::getSubscriberHash($email);
        $route    = self::getSubscribersRoute($hash);
        $response = self::get($route);
        return !empty($response) ? $response : null;
    }

    /**
     * Returns the Subscriber status for the given email
     * @param string $email
     * @return string
     */
    public static function getSubscriberStatus(string $email): string {
        $subscriber = self::getSubscriber($email);
        if (empty($subscriber)) {
            return "";
        }
        return $subscriber["status"];
    }

    /**
     * Adds a Subscriber
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return boolean
     */
    public static function addSubscriber(string $email, string $firstName, string $lastName): bool {
        if (!Config::isMailchimpActive() || !Config::isMailchimpSubscriberActive()) {
            return false;
        }

        $response = self::post(self::getSubscribersRoute(), [
            "status"        => "subscribed",
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::isSuccess($response);
    }

    /**
     * Adds a Subscriber Batch
     * @param array<string,string>[] $subscribers
     * @return boolean
     */
    public static function addSubscriberBatch(array $subscribers): bool {
        if (!Config::isMailchimpActive() || !Config::isMailchimpSubscriberActive()) {
            return false;
        }

        $route = self::getSubscribersRoute();
        foreach ($subscribers as $subscriber) {
            self::post($route, [
                "status"        => "subscribed",
				"email_address" => $subscriber["email"],
                "merge_fields"  => [
                    "FNAME" => $subscriber["firstName"],
                    "LNAME" => $subscriber["lastName"],
                ],
			]);
        }
        return true;
    }

    /**
     * Edits a Subscriber
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $status    Optional.
     * @return boolean
     */
    public static function editSubscriber(string $email, string $firstName, string $lastName, string $status = "subscribed"): bool {
        if (!Config::isMailchimpActive() || !Config::isMailchimpSubscriberActive()) {
            return false;
        }

        $hash     = self::getSubscriberHash($email);
        $route    = self::getSubscribersRoute($hash);
        $response = self::patch($route, [
            "status"        => $status,
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::isSuccess($response);
    }

    /**
     * Deletes a Subscriber
     * @param string $email
     * @return boolean
     */
    public static function deleteSubscriber(string $email): bool {
        if (!Config::isMailchimpActive() || !Config::isMailchimpSubscriberActive()) {
            return false;
        }

        $hash     = self::getSubscriberHash($email);
        $route    = self::getSubscribersRoute($hash);
        $response = self::delete($route);
        return self::isSuccess($response);
    }



    /**
     * Returns a list of Templates
     * @return Select[]
     */
    public static function getTemplates(): array {
        if (!Config::isMailchimpActive()) {
            return [];
        }

        $response = self::get("templates");
        if (!empty($response["templates"])) {
            return Select::create($response["templates"], "id", "name");
        }
        return [];
    }

    /**
     * Sends a Campaign
     * @param string         $subject
     * @param integer        $time
     * @param integer        $templateID
     * @param array{}[]|null $sections   Optional.
     * @param string[]|null  $emails     Optional.
     * @param integer        $folderID   Optional.
     * @return string|null
     */
    public static function sendCampaign(string $subject, int $time, int $templateID, ?array $sections = null, ?array $emails = null, int $folderID = 0): ?string {
        if (!Config::isMailchimpActive() || !Config::isMailchimpCreateActive()) {
            return "disabled";
        }

        // Create the Campaign
        $mailChimpID = self::createCampaign($subject, $emails, $folderID);
        if (empty($mailChimpID)) {
            return null;
        }

        // Place the Content
        if (!self::placeContent($mailChimpID, $templateID, $sections)) {
            return null;
        }

        // We cant send
        if (!Config::isMailchimpSendActive()) {
            return $mailChimpID;
        }

        // Send/Schedule the Campaign
        if (empty($time) || self::mailCampaign($mailChimpID, $time)) {
            return $mailChimpID;
        }

        // Something went wrong
        return null;
    }

    /**
     * Updates a Campaign
     * @param string         $mailChimpID
     * @param string         $subject
     * @param integer        $time
     * @param integer        $templateID
     * @param array{}[]|null $sections    Optional.
     * @param string[]|null  $emails      Optional.
     * @param integer        $folderID    Optional.
     * @return boolean
     */
    public static function updateCampaign(string $mailChimpID, string $subject, int $time, int $templateID, ?array $sections = null, ?array $emails = null, int $folderID = 0): bool {
        if (!Config::isMailchimpActive() || !Config::isMailchimpCreateActive()) {
            return false;
        }

        // Edit the Campaign
        if (!self::editCampaign($mailChimpID, $subject, $emails, $folderID)) {
            return false;
        }

        // Place the Content
        if (!self::placeContent($mailChimpID, $templateID, $sections)) {
            return false;
        }

        // We cant send
        if (!Config::isMailchimpSendActive()) {
            return true;
        }

        // Send/Schedule the Campaign
        if (!empty($time) && !self::mailCampaign($mailChimpID, $time)) {
            return false;
        }

        // All good
        return true;
    }

    /**
     * Creates a Campaign
     * @param string        $subject
     * @param string[]|null $emails   Optional.
     * @param integer       $folderID Optional.
     * @return string
     */
    private static function createCampaign(string $subject, ?array $emails = null, int $folderID = 0): string {
        $recipients = self::parseRecipients($emails);
        if (empty($recipients)) {
            return "";
        }

        $post = [
            "type"       => "regular",
            "recipients" => $recipients,
            "settings"   => [
                "subject_line" => $subject,
                "title"        => $subject,
                "from_name"    => Config::getMailchimpName(),
                "reply_to"     => Config::getMailchimpReplyTo(),
                "to_name"      => "*|FNAME|*",
            ],
        ];
        if (!empty($folderID)) {
            $post["settings"]["folder_id"] = $folderID;
        }
        $response = self::post("campaigns", $post);

        if (self::isSuccess($response)) {
            return $response["id"];
        }
        return "";
    }

    /**
     * Edits a Campaign
     * @param string        $mailChimpID
     * @param string        $subject
     * @param string[]|null $emails      Optional.
     * @param integer       $folderID    Optional.
     * @return boolean
     */
    private static function editCampaign(string $mailChimpID, string $subject, ?array $emails = null, int $folderID = 0): bool {
        $recipients = self::parseRecipients($emails);
        if (empty($recipients)) {
            return false;
        }

        $post = [
            "recipients" => $recipients,
            "settings"   => [
                "subject_line" => $subject,
                "title"        => $subject,
            ],
        ];
        if (!empty($folderID)) {
            $post["settings"]["folder_id"] = $folderID;
        }

        $response = self::patch("campaigns/$mailChimpID", $post);
        return self::isSuccess($response);
    }

    /**
     * Parses the recipients of a Campaign
     * @param string[]|null $emails Optional.
     * @return array<string,mixed>|null
     */
    private static function parseRecipients(?array $emails = null): ?array {
        $recipients = [ "list_id" => Config::getMailchimpList() ];
        if (empty($emails)) {
            return $recipients;
        }

        $conditions = [];
        foreach ($emails as $email) {
            if (Config::isEmailUseWhiteList() && !EmailWhiteList::emailExists($email)) {
                continue;
            }
            $conditions[] = [
                "condition_type" => "EmailAddress",
                "op"             => "is",
                "field"          => "EMAIL",
                "value"          => $email,
            ];
        }
        if (empty($conditions)) {
            return null;
        }

        $recipients["segment_opts"] = [
            "match"      => "any",
            "conditions" => $conditions,
        ];
        return $recipients;
    }

    /**
     * Puts the content into the given MailChimp campaign
     * @param string         $mailChimpID
     * @param integer        $templateID
     * @param array{}[]|null $sections    Optional.
     * @return boolean
     */
    private static function placeContent(string $mailChimpID, int $templateID, ?array $sections = null): bool {
        $post = [ "template" => [ "id" => $templateID ] ];
        if (!empty($sections)) {
            $post["template"]["sections"] = $sections;
        }

        $response = self::put("campaigns/{$mailChimpID}/content", $post);
        return self::isSuccess($response);
    }

    /**
     * Schedules the given MailChimp campaign
     * @param string  $mailChimpID
     * @param integer $time
     * @return boolean
     */
    private static function mailCampaign(string $mailChimpID, int $time): bool {
        if (empty($time)) {
            return false;
        }

        if ($time <= time()) {
            $response = self::post("campaigns/{$mailChimpID}/actions/send");
        } else {
            $response = self::post("campaigns/{$mailChimpID}/actions/schedule", [
                "schedule_time" => gmdate("Y-m-d H:i:s", $time),
                "timewarp"      => false,
            ]);
        }
        return self::isSuccess($response);
    }

    /**
     * Unschedules the given MailChimp campaign
     * @param string $mailChimpID
     * @return boolean
     */
    public static function unscheduleCampaign(string $mailChimpID): bool {
        if (!Config::isMailchimpActive()) {
            return false;
        }

        $response = self::post("campaigns/{$mailChimpID}/actions/unschedule");
        return self::isSuccess($response);
    }

    /**
     * Deletes the given MailChimp campaign
     * @param string $mailChimpID
     * @return boolean
     */
    public static function deleteCampaign(string $mailChimpID): bool {
        if (!Config::isMailchimpActive()) {
            return false;
        }

        $response = self::delete("campaigns/{$mailChimpID}");
        return self::isSuccess($response);
    }



    /**
     * Returns the Content of the given MailChimp campaign
     * @param string $mailChimpID
     * @return array{}
     */
    public static function getContent(string $mailChimpID): array {
        if (!Config::isMailchimpActive()) {
            return [];
        }

        $response = self::get("campaigns/{$mailChimpID}/content");
        return $response;
    }

    /**
     * Returns the Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return array<string,boolean|integer|float>
     */
    public static function getReport(string $mailChimpID): array {
        $result = [
            "hasReport"    => false,
            "opensUnique"  => 0,
            "opensRate"    => 0,
            "clicksUnique" => 0,
            "clicksRate"   => 0,
        ];
        if (!Config::isMailchimpActive() || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $response = self::get("reports/{$mailChimpID}");
        if (!self::isSuccess($response)) {
            return $result;
        }

        $result["hasReport"]    = true;
        $result["emailsSent"]   = $response["emails_sent"];
        $result["opensUnique"]  = $response["opens"]["unique_opens"];
        $result["opensRate"]    = round($response["opens"]["open_rate"] * 1000) / 10;
        $result["clicksUnique"] = $response["clicks"]["unique_clicks"];
        $result["clicksRate"]   = round($response["clicks"]["click_rate"] * 1000) / 10;
        return $result;
    }

    /**
     * Returns the Send Details Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return string[]
     */
    public static function getSendDetails(string $mailChimpID): array {
        $result = [];
        if (!Config::isMailchimpActive() || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $response = self::get("reports/{$mailChimpID}/sent-to", [
            "count" => 2000,
        ]);

        foreach ($response["sent_to"] as $member) {
            $result[] = $member["email_address"];
        }
        return $result;
    }

    /**
     * Returns the Open Details Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return string[]
     */
    public static function getOpenDetails(string $mailChimpID): array {
        $result = [];
        if (!Config::isMailchimpActive() || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $response = self::get("reports/{$mailChimpID}/open-details", [
            "count" => 2000,
        ]);

        foreach ($response["members"] as $member) {
            $result[] = $member["email_address"];
        }
        return $result;
    }

    /**
     * Returns the Click Details Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return string[]
     */
    public static function getClickDetails(string $mailChimpID): array {
        $result = [];
        if (!Config::isMailchimpActive() || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $response = self::get("reports/{$mailChimpID}/click-details");
        if (!empty($response["urls_clicked"][0]["id"])) {
            $clickID = $response["urls_clicked"][0]["id"];
            $report  = self::get("reports/{$mailChimpID}/click-details/{$clickID}/members", [
                "count" => 2000,
            ]);

            foreach ($report["members"] as $member) {
                $result[] = $member["email_address"];
            }
        }
        return $result;
    }
}
