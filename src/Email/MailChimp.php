<?php
namespace Framework\Email;

use Framework\Config\Config;
use Framework\Utils\Arrays;

use DrewM\MailChimp\MailChimp as MailChimpAPI;

/**
 * The MailChimp Provider
 */
class MailChimp {

    private static $loaded = false;
    private static $config = null;
    private static $api    = null;


    /**
     * Creates the MailChimp Provider
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$config = Config::get("mailchimp");

            if (self::$config->active && !empty(self::$config->key)) {
                self::$api = new MailChimpAPI(self::$config->key);
                self::$api->verify_ssl = false;
            }
        }
    }



    /**
     * Returns the Members Route
     * @param string $route Optional.
     * @return string
     */
    private static function getSubscribersRoute(string $route = ""): string {
        $result = "lists/" . self::$config->list . "/members";
        if (!empty($route)) {
            $result .= "/$route";
        }
        return $result;
    }

    /**
     * Returns all the subscribers
     * @param integer $count  Optional.
     * @param integer $offset Optional.
     * @return array
     */
    public static function getAllSubscribers(int $count = 100, int $offset = 0): array {
        self::load();
        if (!self::$api) {
            return null;
        }
        $route  = self::getSubscribersRoute();
        $result = self::$api->get($route, [
            "count"  => $count,
            "offset" => $offset,
        ], 120);
        return $result;
    }

    /**
     * Returns the Subscriber with the given email
     * @param string $email
     * @return array|null
     */
    public static function getSubscriber(string $email) {
        self::load();
        if (!self::$api) {
            return null;
        }
        $hash   = self::$api->subscriberHash($email);
        $route  = self::getSubscribersRoute($hash);
        $result = self::$api->get($route);
        return !empty($result) ? $result : null;
    }

    /**
     * Returns the Subscriber with the given email
     * @param string $email
     * @return integer
     */
    public static function getSubscriberStatus(string $email): int {
        $subscriber = self::getSubscriber($email);
        if (empty($subscriber) || !self::$api->success()) {
            return 0;
        }
        return Subscription::fromString($subscriber["status"]);
    }

    /**
     * Adds a Subscriber
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return boolean
     */
    public static function addSubscriber(string $email, string $firstName, string $lastName): bool {
        self::load();
        if (!self::$api || !self::$config->subscriberActive) {
            return false;
        }
        $route  = self::getSubscribersRoute();
        $result = self::$api->post($route, [
            "status"        => "subscribed",
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::$api->success();
    }

    /**
     * Adds a Subscriber Batch
     * @param array $subscribers
     * @return void
     */
    public static function addSubscriberBatch(array $subscribers): void {
        self::load();
        if (!self::$api || !self::$config->subscriberActive) {
            return;
        }
        $route = self::getSubscribersRoute();
        $batch = self::$api->new_batch();
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
        self::load();
        if (!self::$api || !self::$config->subscriberActive) {
            return false;
        }
        $hash   = self::$api->subscriberHash($email);
        $route  = self::getSubscribersRoute($hash);
        $result = self::$api->patch($route, [
            "status"        => $status,
            "email_address" => $email,
            "merge_fields"  => [
                "FNAME" => $firstName,
                "LNAME" => $lastName,
            ],
        ]);
        return self::$api->success();
    }

    /**
     * Deletes a Subscriber
     * @param string $email
     * @return boolean
     */
    public static function deleteSubscriber(string $email): bool {
        self::load();
        if (!self::$api || !self::$config->subscriberActive) {
            return false;
        }
        $hash  = self::$api->subscriberHash($email);
        $route = self::getSubscribersRoute($hash);
        self::$api->delete($route);
        return self::$api->success();
    }



    /**
     * Returns a list of Templates
     * @return array
     */
    public static function getTemplates() {
        self::load();
        if (!self::$api) {
            return [];
        }
        $data = self::$api->get("templates");
        return Arrays::createSelect($data["templates"], "id", "name");
    }

    /**
     * Sends a Campaign
     * @param string   $subject
     * @param integer  $time
     * @param integer  $templateID
     * @param array    $sections   Optional.
     * @param string[] $emails     Optional.
     * @param integer  $folderID   Optional.
     * @return string|null
     */
    public static function sendCampaign(string $subject, int $time, int $templateID, array $sections = null, array $emails = null, int $folderID = 0) {
        self::load();

        // We do Nothing
        if (!self::$api || !self::$config->createActive) {
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
        if (!self::$config->sendActive) {
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
     * @param string   $mailChimpID
     * @param string   $subject
     * @param integer  $time
     * @param integer  $templateID
     * @param array    $sections    Optional.
     * @param string[] $emails      Optional.
     * @param integer  $folderID    Optional.
     * @return boolean
     */
    public static function updateCampaign(string $mailChimpID, string $subject, int $time, int $templateID, array $sections = null, array $emails = null, int $folderID = 0): bool {
        self::load();

        // We do Nothing
        if (!self::$api || !self::$config->createActive) {
            return false;
        }

        // Edit the Campaign
        if (!self::editCampaign($mailChimpID, $subject, $emails, $folderID)) {
            var_dump(self::$api->getLastResponse());
            return false;
        }

        // Place the Content
        if (!self::placeContent($mailChimpID, $templateID, $sections)) {
            return false;
        }

        // We cant send
        if (!self::$config->sendActive) {
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
     * @param string   $subject
     * @param string[] $emails   Optional.
     * @param integer  $folderID Optional.
     * @return string
     */
    private static function createCampaign(string $subject, array $emails = null, int $folderID = 0): string {
        $post = [
            "type"       => "regular",
            "recipients" => self::parseRecipiets($emails),
            "settings"   => [
                "subject_line" => $subject,
                "title"        => $subject,
                "from_name"    => self::$config->name,
                "reply_to"     => self::$config->replyTo,
                "to_name"      => "*|FNAME|*",
            ],
        ];
        if (!empty($folderID)) {
            $post["settings"]["folder_id"] = $folderID;
        }
        $result = self::$api->post("campaigns", $post, 60);

        if (self::$api->success()) {
            return $result["id"];
        }
        return "";
    }

    /**
     * Edits a Campaign
     * @param string   $mailChimpID
     * @param string   $subject
     * @param string[] $emails      Optional.
     * @param integer  $folderID    Optional.
     * @return boolean
     */
    private static function editCampaign(string $mailChimpID, string $subject, array $emails = null, int $folderID = 0): string {
        $post = [
            "recipients" => self::parseRecipiets($emails),
            "settings"   => [
                "subject_line" => $subject,
                "title"        => $subject,
            ],
        ];
        if (!empty($folderID)) {
            $post["settings"]["folder_id"] = $folderID;
        }
        self::$api->patch("campaigns/$mailChimpID", $post, 60);
        return self::$api->success();
    }

    /**
     * Parses the recipients of a Campaign
     * @param string[] $emails Optional.
     * @return array
     */
    private static function parseRecipiets(array $emails = null) {
        $recipients = [ "list_id" => self::$config->list ];
        if (!empty($emails)) {
            $conditions = [];
            foreach ($emails as $email) {
                $conditions[] = [
                    "condition_type" => "EmailAddress",
                    "op"             => "is",
                    "field"          => "EMAIL",
                    "value"          => $email,
                ];
            }
            $recipients["segment_opts"] = [
                "match"      => "any",
                "conditions" => $conditions,
            ];
        }
        return $recipients;
    }

    /**
     * Puts the content into the given MailChimp campaign
     * @param string  $mailChimpID
     * @param integer $templateID
     * @param array   $sections    Optional.
     * @return boolean
     */
    private static function placeContent(string $mailChimpID, int $templateID, array $sections = null): bool {
        $post = [ "template" => [ "id" => $templateID ] ];
        if (!empty($sections)) {
            $post["template"]["sections"] = $sections;
        }
        $result = self::$api->put("campaigns/{$mailChimpID}/content", $post, 60);
        return self::$api->success();
    }

    /**
     * Schedules the given MailChimp campaign
     * @param string  $mailChimpID
     * @param integer $time
     * @return boolean
     */
    private static function mailCampaign(string $mailChimpID, int $time): bool {
        if (empty($time)) {
            var_dump("no-time");
            return false;
        }
        if ($time <= time()) {
            self::$api->post("campaigns/{$mailChimpID}/actions/send");
        } else {
            self::$api->post("campaigns/{$mailChimpID}/actions/schedule", [
                "schedule_time" => gmdate("Y-m-d H:i:s", $time),
                "timewarp"      => false,
            ]);
        }
        return self::$api->success();
    }

    /**
     * Unschedules the given MailChimp campaign
     * @param string $mailChimpID
     * @return boolean
     */
    public static function unscheduleCampaign(string $mailChimpID): bool {
        self::load();
        if (!self::$api) {
            return false;
        }
        self::$api->post("campaigns/{$mailChimpID}/actions/unschedule");
        return self::$api->success();
    }

    /**
     * Deletes the given MailChimp campaign
     * @param string $mailChimpID
     * @return boolean
     */
    public static function deleteCampaign(string $mailChimpID): bool {
        self::load();
        if (!self::$api) {
            return false;
        }
        self::$api->delete("campaigns/{$mailChimpID}");
        return self::$api->success();
    }



    /**
     * Returns the Content of the given MailChimp campaign
     * @param string $mailChimpID
     * @return array
     */
    public static function getContent(string $mailChimpID): array {
        self::load();
        if (!self::$api) {
            return false;
        }
        $result = self::$api->get("campaigns/{$mailChimpID}/content");
        return $result;
    }

    /**
     * Returns the Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return array
     */
    public static function getReport(string $mailChimpID): array {
        self::load();
        $result = [
            "hasReport"    => false,
            "opensUnique"  => 0,
            "opensRate"    => 0,
            "clicksUnique" => 0,
            "clicksRate"   => 0,
        ];
        if (!self::$api || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $report = self::$api->get("reports/{$mailChimpID}");
        if (empty($report["status"]) || (!empty($report["status"]) && $report["status"] != 404)) {
            $result["hasReport"]    = true;
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
     * @return array
     */
    public static function getSendDetails(string $mailChimpID): array {
        self::load();
        $result = [];
        if (!self::$api || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $report = self::$api->get("reports/{$mailChimpID}/sent-to", [
            "count" => 2000,
        ]);
        foreach ($report["sent_to"] as $member) {
            $result[] = $member["email_address"];
        }
        return $result;
    }

    /**
     * Returns the Open Details Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return array
     */
    public static function getOpenDetails(string $mailChimpID): array {
        self::load();
        $result = [];
        if (!self::$api || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $report = self::$api->get("reports/{$mailChimpID}/open-details", [
            "count" => 2000,
        ]);
        foreach ($report["members"] as $member) {
            $result[] = $member["email_address"];
        }
        return $result;
    }

    /**
     * Returns the Click Details Report for the given MailChimp campaign
     * @param string $mailChimpID
     * @return array
     */
    public static function getClickDetails(string $mailChimpID): array {
        self::load();
        $result = [];
        if (!self::$api || empty($mailChimpID) || $mailChimpID == "disabled") {
            return $result;
        }

        $request = self::$api->get("reports/{$mailChimpID}/click-details");
        if (!empty($request["urls_clicked"][0]["id"])) {
            $clickID = $request["urls_clicked"][0]["id"];
            $report  = self::$api->get("reports/{$mailChimpID}/click-details/{$clickID}/members", [
                "count" => 2000,
            ]);
            foreach ($report["members"] as $member) {
                $result[] = $member["email_address"];
            }
        }
        return $result;
    }
}
