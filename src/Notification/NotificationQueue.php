<?php
namespace Framework\Notification;

use Framework\Request;
use Framework\Auth\Device;
use Framework\Notification\Notification;
use Framework\Notification\NotificationResult;
use Framework\System\Config;
use Framework\Utils\DateTime;
use Framework\Schema\NotificationQueueSchema;
use Framework\Schema\NotificationQueueEntity;
use Framework\Schema\NotificationQueueColumn;
use Framework\Schema\NotificationQueueQuery;

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
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        $query->credentialID->equal($credentialID);
        return self::entityExists($query);
    }



    /**
     * Creates the List Query
     * @param Request $request
     * @return NotificationQueueQuery
     */
    protected static function createListQuery(Request $request): NotificationQueueQuery {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = new NotificationQueueQuery();
        $query->search([
            NotificationQueueColumn::Title,
            NotificationQueueColumn::Body,
            NotificationQueueColumn::CredentialFirstName,
            NotificationQueueColumn::CredentialLastName,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
        return $query;
    }



    /**
     * Returns the Unset Notifications in the last hour
     * @return NotificationQueueEntity[]
     */
    public static function getAllUnsent(): array {
        $query = new NotificationQueueQuery();
        $query->notificationResult->equal(NotificationResult::NotProcessed->name);
        $query->createdTime->greaterThan(DateTime::getLastXHours(1));
        $query->createdTime->orderByDesc();
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
        $query = new NotificationQueueQuery();
        $query->credentialID->equal($credentialID);
        $query->currentUser->equal($currentUser);
        $query->sentTime->equal(0);
        $query->createdTime->greaterThan($time - 3600);
        $query->createdTime->orderByDesc();
        return self::getEntityList($query);
    }

    /**
     * Returns all the Notifications for the given Credential
     * @param integer $credentialID
     * @param integer $currentUser
     * @param Request $sort
     * @return NotificationQueueEntity[]
     */
    public static function getAllForCredential(int $credentialID, int $currentUser, Request $sort): array {
        $query = new NotificationQueueQuery();
        $query->credentialID->equal($credentialID);
        $query->currentUser->equal($currentUser);
        $query->isDiscarded->isFalse();
        $query->createdTime->greaterThan(DateTime::getLastXDays(30));
        $query->createdTime->orderByDesc();
        return self::getEntityList($query, $sort);
    }

    /**
     * Returns the total amount of unread Notifications
     * @param integer $credentialID
     * @param integer $currentUser
     * @return integer
     */
    public static function getUnreadAmount(int $credentialID, int $currentUser): int {
        $query = new NotificationQueueQuery();
        $query->credentialID->equal($credentialID);
        $query->currentUser->equal($currentUser);
        $query->isRead->isFalse();
        $query->isDiscarded->isFalse();
        $query->createdTime->greaterThan(DateTime::getLastXDays(30));
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
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getNotificationDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new NotificationQueueQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }



    /**
     * Marks the given Notification as read for the given Credential
     * @param integer $notificationID
     * @return boolean
     */
    public static function markAsRead(int $notificationID): bool {
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        return self::editEntity($query, isRead: true);
    }

    /**
     * Discards the given Notification for the given Credential
     * @param integer $notificationID
     * @return boolean
     */
    public static function discard(int $notificationID): bool {
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
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

            if (!Config::isNotificationActive()) {
                $notificationResult = NotificationResult::InactiveSend;
            } elseif (count($playerIDs) === 0) {
                $notificationResult = NotificationResult::NoDevices;
            } else {
                $externalID = Notification::sendToSome(
                    $elem->title,
                    $elem->body,
                    $elem->url,
                    $elem->dataType,
                    $elem->dataID,
                    $playerIDs,
                );
                if ($externalID === null) {
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
