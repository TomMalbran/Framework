<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Email\Email;
use Framework\Email\EmailResult;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;

/**
 * The Email Queue
 */
class EmailQueue {

    /**
     * Loads the Email Queue Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("emailQueue");
    }



    /**
     * Returns an Email Queue with the given ID
     * @param integer $emailID
     * @return Model
     */
    public static function getOne(int $emailID): Model {
        return self::schema()->getOne($emailID);
    }

    /**
     * Returns true if the given Email Queue exists
     * @param integer $emailID
     * @return boolean
     */
    public static function exists(int $emailID): bool {
        return self::schema()->exists($emailID);
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

        $query = Query::createSearch([ "sendTo", "subject", "message" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Emails from the Queue
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        $query = self::createQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns all the not sent Emails from the Queue
     * @return array{}[]
     */
    public static function getAllUnsent(): array {
        $query = Query::create("sentTime", "=", 0);
        $query->orderBy("createdTime", false);
        return self::schema()->getAll($query);
    }

    /**
     * Returns the total amount of Emails from the Queue
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::createQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Adds the given Email to the Queue
     * @param Model           $template
     * @param string[]|string $sendTo
     * @param string|null     $message  Optional.
     * @param string|null     $subject  Optional.
     * @param boolean         $sendNow  Optional.
     * @return boolean
     */
    public static function add(Model $template, array|string $sendTo, ?string $message = null, ?string $subject = null, bool $sendNow = false): bool {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;

        if (empty($sendTo)) {
            return false;
        }
        $emailID = self::schema()->create([
            "templateCode" => $template->id,
            "sendTo"       => JSON::encode($sendTo),
            "subject"      => $subject,
            "message"      => $message,
            "emailResult"  => EmailResult::NotProcessed,
            "sentTime"     => 0,
        ]);

        if (!$sendNow) {
            return true;
        }
        $email = self::getOne($emailID);
        return self::send($email);
    }



    /**
     * Sends all the Unsent Emails
     * @return boolean
     */
    public static function sendAll(): bool {
        $emails = self::getAllUnsent();
        $result = true;
        foreach ($emails as $email) {
            if (!self::send($email)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Sends the given Email
     * @param Model|array{} $email
     * @return boolean
     */
    public static function send(Model|array $email): bool {
        $emailResult = EmailResult::NoEmails;
        foreach ($email["sendTo"] as $sendTo) {
            if (!empty($sendTo)) {
                $emailResult = Email::send($sendTo, $email["subject"], $email["message"]);
            }
        }
        return self::markAsSent($email["emailID"], $emailResult);
    }

    /**
     * Marks the Email as Sent
     * @param integer $emailID
     * @param string  $emailResult
     * @return boolean
     */
    public static function markAsSent(int $emailID, string $emailResult): bool {
        return self::schema()->edit($emailID, [
            "emailResult" => $emailResult,
            "sentTime"    => time(),
        ]);
    }

    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::schema()->remove($query);
    }
}
