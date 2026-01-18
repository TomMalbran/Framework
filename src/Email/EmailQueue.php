<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Email\Email;
use Framework\Email\EmailResult;
use Framework\Email\Schema\EmailQueueSchema;
use Framework\Email\Schema\EmailQueueEntity;
use Framework\Email\Schema\EmailQueueColumn;
use Framework\Email\Schema\EmailQueueQuery;
use Framework\Email\Schema\EmailContentEntity;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Email Queue
 */
class EmailQueue extends EmailQueueSchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return EmailQueueQuery
     */
    #[\Override]
    protected static function createListQuery(Request $request): EmailQueueQuery {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStartHour("fromDate", "fromHour");
        $toTime   = $request->toDayEndHour("toDate", "toHour");
        $dataID   = $request->getInt("dataID");
        $results  = $request->getStrings("results");

        $query = new EmailQueueQuery();
        $query->search([
            EmailQueueColumn::SendTo,
            EmailQueueColumn::Subject,
            EmailQueueColumn::Message,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
        $query->dataID->equalIf($dataID);
        $query->emailResult->in($results);
        return $query;
    }

    /**
     * Returns all the not sent Queued Emails in the last hour
     * @return EmailQueueEntity[]
     */
    public static function getAllUnsent(): array {
        $query = new EmailQueueQuery();
        $query->sentTime->equal(0);
        $query->startOr();
        $query->createdTime->greaterThan(DateTime::getLastXHours(1));
        $query->sendTime->greaterThan(DateTime::getLastXHours(1));
        $query->endOr();
        $query->createdTime->orderByDesc();
        $query->limit(Config::getEmailLimit());
        return self::getEntityList($query);
    }



    /**
     * Adds the given Email to the Queue
     * @param EmailContentEntity $content
     * @param string[]|string    $sendTo
     * @param string|null        $message Optional.
     * @param string|null        $subject Optional.
     * @param bool               $sendNow Optional.
     * @param int                $dataID  Optional.
     * @return bool
     */
    public static function add(EmailContentEntity $content, array|string $sendTo, ?string $message = null, ?string $subject = null, bool $sendNow = false, int $dataID = 0): bool {
        $sendTo    = Arrays::toStrings($sendTo);
        $subject ??= $content->subject;
        $message ??= $content->message;

        if (count($sendTo) === 0) {
            return false;
        }
        $emailID = self::createEntity(
            emailCode:   $content->emailCode,
            sendTo:      JSON::encode($sendTo),
            subject:     $subject,
            message:     $message,
            emailResult: EmailResult::NotProcessed->name,
            sendTime:    time(),
            sentTime:    0,
            dataID:      $dataID,
        );

        if (!$sendNow) {
            return true;
        }
        $email = self::getByID($emailID);
        return self::send($email, $sendNow);
    }



    /**
     * Sends all the Unsent Emails
     * @return bool
     */
    public static function sendAll(): bool {
        $emails = self::getAllUnsent();
        $result = true;
        foreach ($emails as $email) {
            if (!self::send($email, false)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Sends the given Email
     * @param EmailQueueEntity $email
     * @param bool             $sendAlways
     * @return bool
     */
    public static function send(EmailQueueEntity $email, bool $sendAlways): bool {
        $emailResult = EmailResult::NoEmails;
        $sendTos     = Arrays::toStrings($email->sendTo, withoutEmpty: true);

        foreach ($sendTos as $sendTo) {
            $emailResult = Email::send(
                $sendTo,
                $email->subject,
                $email->message,
                $sendAlways,
            );
        }
        return self::markAsSent($email->emailID, $emailResult);
    }

    /**
     * Marks the given Email(s) as Not Sent
     * @param int[]|int $emailID
     * @return bool
     */
    public static function markAsNotSent(array|int $emailID): bool {
        $query = new EmailQueueQuery();
        $query->emailID->in(Arrays::toInts($emailID));

        return self::editEntity(
            $query,
            emailResult: EmailResult::NotProcessed->name,
            sendTime:    time(),
            sentTime:    0,
        );
    }

    /**
     * Marks the given Email as Sent
     * @param int         $emailID
     * @param EmailResult $emailResult
     * @return bool
     */
    public static function markAsSent(int $emailID, EmailResult $emailResult): bool {
        return self::editEntity(
            $emailID,
            emailResult: $emailResult->name,
            sentTime:    time(),
        );
    }

    /**
     * Deletes the items older than some days
     * @return bool
     */
    public static function deleteOld(): bool {
        $days  = Config::getEmailDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new EmailQueueQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }
}
