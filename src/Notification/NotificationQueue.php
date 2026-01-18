<?php
namespace Framework\Notification;

use Framework\Request;
use Framework\Auth\Device;
use Framework\Notification\Notification;
use Framework\Notification\NotificationResult;
use Framework\Notification\Schema\NotificationQueueSchema;
use Framework\Notification\Schema\NotificationQueueEntity;
use Framework\Notification\Schema\NotificationQueueColumn;
use Framework\Notification\Schema\NotificationQueueQuery;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\JSON;

/**
 * The Notification Queue
 */
class NotificationQueue extends NotificationQueueSchema {

    /**
     * Returns true if there is a Notification with the given ID for the given Credential
     * @param int $notificationID
     * @param int $credentialID
     * @return bool
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
    #[\Override]
    protected static function createListQuery(Request $request): NotificationQueueQuery {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStartHour("fromDate", "fromHour");
        $toTime   = $request->toDayEndHour("toDate", "toHour");
        $results  = $request->getStrings("results");

        $query = new NotificationQueueQuery();
        $query->search([
            NotificationQueueColumn::Title,
            NotificationQueueColumn::Message,
            NotificationQueueColumn::CredentialFirstName,
            NotificationQueueColumn::CredentialLastName,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
        $query->notificationResult->in($results);
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
     * @param int $credentialID
     * @param int $currentUser
     * @param int $time
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
     * @param int     $credentialID
     * @param int     $currentUser
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
     * @param int $credentialID
     * @param int $currentUser
     * @return int
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
     * @param int    $credentialID
     * @param int    $currentUser
     * @param string $title
     * @param string $message
     * @param string $url
     * @param string $dataType
     * @param int    $dataID
     * @return int
     */
    public static function add(
        int $credentialID,
        int $currentUser,
        string $title,
        string $message,
        string $url,
        string $dataType,
        int $dataID
    ): int {
        return self::createEntity(
            credentialID:       $credentialID,
            currentUser:        $currentUser,
            title:              $title,
            message:            $message,
            url:                $url,
            dataType:           $dataType,
            dataID:             $dataID,
            notificationResult: NotificationResult::NotProcessed->name,
        );
    }

    /**
     * Deletes the given Notification
     * @param int $notificationID
     * @return bool
     */
    public static function delete(int $notificationID): bool {
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return bool
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
     * @param int $notificationID
     * @return bool
     */
    public static function markAsRead(int $notificationID): bool {
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        return self::editEntity($query, isRead: true);
    }

    /**
     * Discards the given Notification for the given Credential
     * @param int $notificationID
     * @return bool
     */
    public static function discard(int $notificationID): bool {
        $query = new NotificationQueueQuery();
        $query->notificationID->equal($notificationID);
        return self::editEntity($query, isDiscarded: true);
    }



    /**
     * Sends all the Unsent Notifications
     * @return bool
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
                    $elem->message,
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
                playerIDs:          JSON::encode($playerIDs),
                sentTime:           time(),
            );
        }
        return $result;
    }
}
