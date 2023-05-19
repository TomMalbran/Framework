<?php
namespace Framework\Notification;

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Auth\Device;
use Framework\Notification\Notification;
use Framework\Utils\DateTime;

/**
 * The Notification Queue
 */
class NotificationQueue {

    /**
     * Loads the Notification Queue Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("notificationQueue");
    }

    /**
     * Loads the Notification Credentials Schema
     * @return Schema
     */
    public static function credentialSchema(): Schema {
        return Factory::getSchema("notificationCredentials");
    }



    /**
     * Returns a Notification with the given ID
     * @param integer $notificationID
     * @return Model
     */
    public static function getOne(int $notificationID): Model {
        return self::schema()->getOne($notificationID);
    }

    /**
     * Returns true if there is a Notification with the given ID
     * @param integer $notificationID
     * @return boolean
     */
    public static function exists(int $notificationID): bool {
        return self::schema()->exists($notificationID);
    }

    /**
     * Returns true if there is a Notification with the given ID for the given Credential
     * @param integer $notificationID
     * @param integer $credentialID
     * @return boolean
     */
    public static function existsForCredential(int $notificationID, int $credentialID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::schema()->exists($query);
    }



    /**
     * Returns the List Query
     * @param Request $request
     * @return Query
     */
    private static function createQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "title", "body" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Notifications from the Queue
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        $query = self::createQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns the total amount of Notifications from the Queue
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::createQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Returns the Unset Notifications for the given Type
     * @return array{}[]
     */
    public static function getAllUnsent(): array {
        $query = Query::create("sentTime", "=", 0);
        $query->add("createdTime", ">", DateTime::getLastXHours(1));
        $query->orderBy("createdTime", false);
        return self::schema()->getAll($query);
    }

    /**
     * Returns the Unset Notifications for the given Credential
     * @param integer $credentialID
     * @param integer $currentUser
     * @param integer $time
     * @return array{}[]
     */
    public static function getUnsentForCredential(int $credentialID, int $currentUser, int $time): array {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("currentUser", "=", $currentUser);
        $query->add("sentTime",    "=", 0);
        $query->add("createdTime", ">", $time - 3600);
        $query->orderBy("createdTime", false);
        return self::schema()->getAll($query);
    }

    /**
     * Returns all the Notifications for the given Credential
     * @param integer $credentialID
     * @param integer $currentUser
     * @param Request $request
     * @return array{}[]
     */
    public static function getAllForCredential(int $credentialID, int $currentUser, Request $request): array {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("currentUser", "=", $currentUser);
        $query->add("isDiscarded", "=", 0);
        $query->add("createdTime", ">", DateTime::getLastXDays(30));
        $query->orderBy("createdTime", false);
        return self::credentialSchema()->getAll($query, $request);
    }

    /**
     * Returns the total amount of unread Notifications
     * @param integer $credentialID
     * @param integer $currentUser
     * @return integer
     */
    public static function getUnreadAmount(int $credentialID, int $currentUser): int {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("currentUser", "=", $currentUser);
        $query->add("isRead",      "=", 0);
        $query->add("isDiscarded", "=", 0);
        $query->add("createdTime", ">", DateTime::getLastXDays(30));
        return self::credentialSchema()->getTotal($query);
    }



    /**
     * Adds a new Notification
     * @param string  $title
     * @param string  $body
     * @param string  $url
     * @param string  $type
     * @param integer $dataID
     * @param array{} $credentials
     * @return boolean
     */
    public static function add(
        string $title,
        string $body,
        string $url,
        string $type,
        int $dataID,
        array $credentials
    ): bool {
        if (empty($credentials)) {
            return false;
        }

        $notificationID = self::schema()->create([
            "title"  => $title,
            "body"   => $body,
            "url"    => $url,
            "type"   => $type,
            "dataID" => $dataID,
        ]);
        foreach ($credentials as $credentialID => $currentUser) {
            self::credentialSchema()->replace([
                "NOTIFICATION_ID" => $notificationID,
                "CREDENTIAL_ID"   => $credentialID,
                "currentUser"     => $currentUser,
            ]);
        }
        return true;
    }

    /**
     * Deletes the given Notification
     * @param integer $notificationID
     * @return boolean
     */
    public static function delete(int $notificationID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        self::credentialSchema()->remove($query);
        return self::schema()->remove($query);
    }

    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        self::credentialSchema()->remove($query);
        return self::schema()->remove($query);
    }



    /**
     * Marks the given Notification as read for the given Credential
     * @param integer $notificationID
     * @param integer $credentialID
     * @return boolean
     */
    public static function markAsRead(int $notificationID, int $credentialID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::credentialSchema()->edit($query, [
            "isRead" => 1,
        ]);
    }

    /**
     * Discards the given Notification for the given Credential
     * @param integer $notificationID
     * @param integer $credentialID
     * @return boolean
     */
    public static function discard(int $notificationID, int $credentialID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::credentialSchema()->edit($query, [
            "isDiscarded" => 1,
        ]);
    }



    /**
     * Sends all the Unsent Notifications
     * @return boolean
     */
    public static function sendAll(): bool {
        $notifications = self::getAllUnsent();
        $result        = true;

        foreach ($notifications as $notification) {
            $credentialIDs = array_keys($notification["credentials"]);
            $playerIDs     = Device::getAllForCredentials($credentialIDs);
            $externalID    = Notification::sendToSome(
                $notification["title"],
                $notification["body"],
                $notification["url"],
                $notification["type"],
                $notification["dataID"],
                $playerIDs
            );
            if (!empty($externalID)) {
                self::schema()->edit($notification["notificationID"], [
                    "sentTime"   => time(),
                    "externalID" => $externalID,
                ]);
            }
        }
        return $result;
    }
}
