<?php
namespace Framework\Provider;

use Framework\Email\EmailWhiteList;
use Framework\System\Config;
use Framework\Utils\Select;

use DrewM\MailChimp\MailChimp as MailChimpAPI;

/**
 * The MailChimp Provider
 */
class MailChimp {

    private static ?MailChimpAPI $api = null;


    /**
     * Creates the MailChimp Provider
     * @return MailChimpAPI|null
     */
    private static function api(): ?MailChimpAPI {
        if (empty(self::$api) && Config::isMailchimpActive()) {
            self::$api = new MailChimpAPI(Config::getMailchimpKey());
            self::$api->verify_ssl = false;
        }
        return self::$api;
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

        $route  = self::getSubscribersRoute();
        $result = self::api()->get($route, [
            "count"  => $count,
            "offset" => $offset,
        ], 120);
        return $result === false ? [] : $result;
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

        $hash   = self::api()->subscriberHash($email);
        $route  = self::getSubscribersRoute($hash);
        $result = self::api()->get($route);
        return !empty($result) ? $result : null;
    }

    /**
     * Returns the Subscriber status for the given email
     * @param string $email
     * @return string
     */
    public static function getSubscriberStatus(string $email): string {
        $subscriber = self::getSubscriber($email);
        if (empty($subscriber) || !self::api()->success()) {
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

        self::api()->post(self::getSubscribersRoute(), [
            "status"        => "subscribed",
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::api()->success();
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
        $batch = self::api()->new_batch();
        foreach ($subscribers as $index => $subscriber) {
            $batch->post("op$index", $route, [
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

        $hash  = self::api()->subscriberHash($email);
        $route = self::getSubscribersRoute($hash);
        self::api()->patch($route, [
            "status"        => $status,
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::api()->success();
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

        $hash  = self::api()->subscriberHash($email);
        $route = self::getSubscribersRoute($hash);
        self::api()->delete($route);
        return self::api()->success();
    }



    /**
     * Returns a list of Templates
     * @return Select[]
     */
    public static function getTemplates(): array {
        if (!Config::isMailchimpActive()) {
            return [];
        }

        $data = self::api()->get("templates");
        if ($data === false) {
            return [];
        }

        return Select::create($data["templates"], "id", "name");
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
        $result = self::api()->post("campaigns", $post, 300);

        if ($result !== false && self::api()->success()) {
            return $result["id"];
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
        self::api()->patch("campaigns/$mailChimpID", $post, 60);
        return self::api()->success();
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
        self::api()->put("campaigns/{$mailChimpID}/content", $post, 60);
        return self::api()->success();
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
            self::api()->post("campaigns/{$mailChimpID}/actions/send");
        } else {
            self::api()->post("campaigns/{$mailChimpID}/actions/schedule", [
                "schedule_time" => gmdate("Y-m-d H:i:s", $time),
                "timewarp"      => false,
            ]);
        }
        return self::api()->success();
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

        self::api()->post("campaigns/{$mailChimpID}/actions/unschedule");
        return self::api()->success();
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

        self::api()->delete("campaigns/{$mailChimpID}");
        return self::api()->success();
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

        $result = self::api()->get("campaigns/{$mailChimpID}/content");
        return $result === false ? [] : $result;
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

        $report = self::api()->get("reports/{$mailChimpID}");
        if (empty($report)) {
            return $result;
        }

        // @phpstan-ignore isset.offset
        if (!isset($report["status"]) || (isset($report["status"]) && $report["status"] != 404)) {
            $result["hasReport"]    = true;
            $result["emailsSent"]   = $report["emails_sent"];
            $result["opensUnique"]  = $report["opens"]["unique_opens"];
            $result["opensRate"]    = round($report["opens"]["open_rate"] * 1000) / 10;
            $result["clicksUnique"] = $report["clicks"]["unique_clicks"];
            $result["clicksRate"]   = round($report["clicks"]["click_rate"] * 1000) / 10;
        }
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

        $report = self::api()->get("reports/{$mailChimpID}/sent-to", [
            "count" => 2000,
        ]);
        if ($report === false) {
            return $result;
        }

        foreach ($report["sent_to"] as $member) {
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

        $report = self::api()->get("reports/{$mailChimpID}/open-details", [
            "count" => 2000,
        ]);
        if ($report === false) {
            return $result;
        }

        foreach ($report["members"] as $member) {
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

        $request = self::api()->get("reports/{$mailChimpID}/click-details");
        if (!empty($request["urls_clicked"][0]["id"])) {
            $clickID = $request["urls_clicked"][0]["id"];
            $report  = self::api()->get("reports/{$mailChimpID}/click-details/{$clickID}/members", [
                "count" => 2000,
            ]);
            if ($report === false) {
                return $result;
            }

            foreach ($report["members"] as $member) {
                $result[] = $member["email_address"];
            }
        }
        return $result;
    }
}
