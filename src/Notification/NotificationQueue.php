<?php
namespace Framework\Notification;

use Framework\Request;
use Framework\Database\Query;
use Framework\Auth\Device;
use Framework\Notification\Notification;
use Framework\Notification\NotificationResult;
use Framework\System\Config;
use Framework\Utils\DateTime;
use Framework\Schema\NotificationQueueSchema;
use Framework\Schema\NotificationQueueEntity;

/**
 * The Notification Queue
 */
class NotificationQueue extends NotificationQueueSchema {

    /**
     * Returns true if there is a Notification with the given ID for the given Credential
     * @param integer $notificationID
     * @param integer $credentialID
     * @return boolean
     */
    public static function existsForCredential(int $notificationID, int $credentialID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::entityExists($query);
    }



    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([
            "title", "body",
            "CONCAT(credential.firstName, ' ', credential.lastName)",
        ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }



    /**
     * Returns the Unset Notifications in the last hour
     * @return NotificationQueueEntity[]
     */
    public static function getAllUnsent(): array {
        $query = Query::create("notificationResult", "=", NotificationResult::NotProcessed->name);
        $query->add("createdTime", ">", DateTime::getLastXHours(1));
        $query->orderBy("createdTime", false);
        return self::getEntityList($query);
    }

    /**
     * Returns the Unset Notifications for the given Credential
     * @param integer $credentialID
     * @param integer $currentUser
     * @param integer $time
     * @return NotificationQueueEntity[]
     */
    public static function getUnsentForCredential(int $credentialID, int $currentUser, int $time): array {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("currentUser", "=", $currentUser);
        $query->add("sentTime",    "=", 0);
        $query->add("createdTime", ">", $time - 3600);
        $query->orderBy("createdTime", false);
        return self::getEntityList($query);
    }

    /**
     * Returns all the Notifications for the given Credential
     * @param integer $credentialID
     * @param integer $currentUser
     * @param Request $request
     * @return NotificationQueueEntity[]
     */
    public static function getAllForCredential(int $credentialID, int $currentUser, Request $request): array {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("currentUser", "=", $currentUser);
        $query->add("isDiscarded", "=", 0);
        $query->add("createdTime", ">", DateTime::getLastXDays(30));
        $query->orderBy("createdTime", false);
        return self::getEntityList($query, $request);
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
        return self::getEntityTotal($query);
    }



    /**
     * Adds a new Notification
     * @param integer $credentialID
     * @param integer $currentUser
     * @param string  $title
     * @param string  $body
     * @param string  $url
     * @param string  $dataType
     * @param integer $dataID
     * @return integer
     */
    public static function add(
        int $credentialID,
        int $currentUser,
        string $title,
        string $body,
        string $url,
        string $dataType,
        int $dataID
    ): int {
        return self::createEntity(
            credentialID:       $credentialID,
            currentUser:        $currentUser,
            title:              $title,
            body:               $body,
            url:                $url,
            dataType:           $dataType,
            dataID:             $dataID,
            notificationResult: NotificationResult::NotProcessed->name,
        );
    }

    /**
     * Deletes the given Notification
     * @param integer $notificationID
     * @return boolean
     */
    public static function delete(int $notificationID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getNotificationDeleteDays();
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("notification_queue.createdTime", "<", $time);
        return self::removeEntity($query);
    }



    /**
     * Marks the given Notification as read for the given Credential
     * @param integer $notificationID
     * @return boolean
     */
    public static function markAsRead(int $notificationID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        return self::editEntity($query, isRead: true);
    }

    /**
     * Discards the given Notification for the given Credential
     * @param integer $notificationID
     * @return boolean
     */
    public static function discard(int $notificationID): bool {
        $query = Query::create("NOTIFICATION_ID", "=", $notificationID);
        return self::editEntity($query, isDiscarded: true);
    }



    /**
     * Sends all the Unsent Notifications
     * @return boolean
     */
    public static function sendAll(): bool {
        $list   = self::getAllUnsent();
        $result = true;

        foreach ($list as $elem) {
            $notificationResult = NotificationResult::Sent;
            $playerIDs          = Device::getAllForCredential($elem->credentialID);
            $externalID         = "";

            if (empty($playerIDs)) {
                $notificationResult = NotificationResult::NoDevices;
            } else {
                $externalID = Notification::sendToSome(
                    $elem->title,
                    $elem->body,
                    $elem->url,
                    $elem->dataType,
                    $elem->dataID,
                    $playerIDs
                );
                if (empty($externalID)) {
                    $notificationResult = NotificationResult::ProviderError;
                }
            }

            self::editEntity(
                $elem->notificationID,
                notificationResult: $notificationResult->name,
                externalID:         $externalID,
                sentTime:           time(),
            );
        }
        return $result;
    }
}
