<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Email\Email;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Email Queue
 */
class Queue {

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
     * Returns the Filter Query
     * @param Request $request
     * @return Query
     */
    private static function getFilterQuery(Request $request): Query {
        $query = Query::createSearch([ "subject", "sendTo" ], $request->search);
        $query->addIf("createdTime", ">", $request->fromTime);
        $query->addIf("createdTime", "<", $request->toTime);
        return $query;
    }

    /**
     * Returns all the Emails from the Queue
     * @param Request $request
     * @return array
     */
    public static function getAll(Request $request): array {
        $query = self::getFilterQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns all the not sent Emails from the Queue
     * @return array
     */
    public static function getAllUnsent(): array {
        $query = Query::create("sentTime", "=", 0);
        return self::schema()->getAll($query);
    }

    /**
     * Returns the total amount of Emails from the Queue
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::getFilterQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Adds the given Email to the Queue
     * @param Model           $template
     * @param string[]|string $sendTo
     * @param string          $message  Optional.
     * @param string          $subject  Optional.
     * @param boolean         $sendNow  Optional.
     * @return boolean
     */
    public static function add(Model $template, array|string $sendTo, string $message = null, string $subject = null, bool $sendNow = false): bool {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;

        if (empty($sendTo)) {
            return false;
        }
        $emailID = self::schema()->create([
            "templateCode" => $template->id,
            "sendTo"       => JSON::encode($sendTo),
            "sendAs"       => $template->sendAs   ?: "",
            "sendName"     => $template->sendName ?: "",
            "subject"      => $subject,
            "message"      => $message,
            "sentSuccess"  => 0,
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
     * @return void
     */
    public static function sendAll() {
        $emails = self::getAllUnsent();
        foreach ($emails as $email) {
            self::send($email);
        }
    }

    /**
     * Sends the given Email
     * @param Model|array $email
     * @return boolean
     */
    public static function send($email): bool {
        $success = false;
        foreach ($email["sendToParts"] as $sendTo) {
            if (!empty($sendTo)) {
                $success = Email::sendHTML($sendTo, $email["sendAs"], $email["sendName"], $email["subject"], $email["message"]);
            }
        }
        return self::schema()->edit($email["emailID"], [
            "sentSuccess" => $success ? 1 : 0,
            "sentTime"    => time(),
        ]);
    }
}
