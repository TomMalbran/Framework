<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Database\Query;
use Framework\Email\Email;
use Framework\Email\EmailResult;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Schema\EmailQueueSchema;
use Framework\Schema\EmailQueueEntity;
use Framework\Schema\EmailTemplateEntity;

/**
 * The Email Queue
 */
class EmailQueue extends EmailQueueSchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "sendTo", "subject", "message" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        $query->addIf("dataID",      "=", $request->dataID);
        return $query;
    }

    /**
     * Returns all the not sent Queued Emails in the last hour
     * @return EmailQueueEntity[]
     */
    public static function getAllUnsent(): array {
        $query = Query::create("sentTime", "=", 0);
        $query->startOr();
        $query->add("createdTime", ">", DateTime::getLastXHours(1));
        $query->add("sendTime", ">", DateTime::getLastXHours(1));
        $query->endOr();
        $query->orderBy("createdTime", false);
        $query->limitIf(Config::getEmailLimit());
        return self::getEntityList($query);
    }



    /**
     * Adds the given Email to the Queue
     * @param EmailTemplateEntity $template
     * @param string[]|string     $sendTo
     * @param string|null         $message  Optional.
     * @param string|null         $subject  Optional.
     * @param boolean             $sendNow  Optional.
     * @param integer             $dataID   Optional.
     * @return boolean
     */
    public static function add(EmailTemplateEntity $template, array|string $sendTo, ?string $message = null, ?string $subject = null, bool $sendNow = false, int $dataID = 0): bool {
        $sendTo  = Arrays::toStrings($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;

        if (empty($sendTo)) {
            return false;
        }
        $emailID = self::createEntity(
            templateCode: $template->templateCode,
            sendTo:       JSON::encode($sendTo),
            subject:      $subject,
            message:      $message,
            emailResult:  EmailResult::NotProcessed->name,
            sendTime:     time(),
            sentTime:     0,
            dataID:       $dataID,
        );

        if (!$sendNow) {
            return true;
        }
        $email = self::getByID($emailID);
        return self::send($email, $sendNow);
    }



    /**
     * Sends all the Unsent Emails
     * @return boolean
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
     * @param boolean          $sendAlways
     * @return boolean
     */
    public static function send(EmailQueueEntity $email, bool $sendAlways): bool {
        $emailResult = EmailResult::NoEmails;
        foreach ($email->sendTo as $sendTo) {
            if (!empty($sendTo)) {
                $emailResult = Email::send(
                    $sendTo,
                    $email->subject,
                    $email->message,
                    $sendAlways,
                );
            }
        }
        return self::markAsSent($email->emailID, $emailResult);
    }

    /**
     * Marks the given Email(s) as Not Sent
     * @param integer[]|integer $emailID
     * @return boolean
     */
    public static function markAsNotSent(array|int $emailID): bool {
        $emailIDs = Arrays::toInts($emailID);
        $query    = Query::create("EMAIL_ID", "IN", $emailIDs);
        return self::editEntity(
            $query,
            emailResult: EmailResult::NotProcessed->name,
            sendTime:    time(),
            sentTime:    0,
        );
    }

    /**
     * Marks the given Email as Sent
     * @param integer     $emailID
     * @param EmailResult $emailResult
     * @return boolean
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
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getEmailDeleteDays();
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::removeEntity($query);
    }
}
