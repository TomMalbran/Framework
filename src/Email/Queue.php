<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Email Queue
 */
class Queue {
    
    private static $loaded = false;
    private static $schema = null;
    
    
    /**
     * Loads the Email Queue Schema
     * @return Schema
     */
    public static function getSchema(): Schema {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("emailQueue");
        }
        return self::$schema;
    }
    
    

    /**
     * Returns an Email Queue with the given Code
     * @param integer $emailID
     * @return Model
     */
    public static function getOne(int $emailID): Model {
        return self::getSchema()->getOne($emailID);
    }
    
    /**
     * Returns true if the given  Email Queue exists
     * @param integer $emailID
     * @return boolean
     */
    public static function exists(int $emailID): bool {
        return self::getSchema()->exists($emailID);
    }
    
    /**
     * Returns all the Email Queues
     * @param Request $request
     * @return array
     */
    public static function getAll(Request $request): array {
        return self::getSchema()->getAll(null, $request);
    }

    /**
     * Returns all the Email Queues
     * @return array
     */
    public static function getAllUnsent(): array {
        $query = Query::create("sentTime", "=", 0);
        return self::getSchema()->getAll($query);
    }

    /**
     * Returns the total amount of Email Queues
     * @return integer
     */
    public static function getTotal(): int {
        return self::getSchema()->getTotal();
    }
    


    /**
     * Adds the given Email to the Queue
     * @param Model           $template
     * @param string|string[] $sendTo
     * @param string          $subject  Optional.
     * @param string          $message  Optional.
     * @return integer
     */
    public static function add(Model $template, $sendTo, string $subject = null, string $message = null): bool {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;

        if (empty($sendTo)) {
            return 0;
        }
        return self::getSchema()->create([
            "templateCode" => $template->id,
            "sendTo"       => JSON::encode($sendTo),
            "sendAs"       => $template->sendAs   ?: "",
            "sendName"     => $template->sendName ?: "",
            "subject"      => $subject,
            "message"      => $message,
            "sentSuccess"  => 0,
            "sentTime"     => 0,
        ]);
    }

    /**
     * Marks the given Email as sent
     * @param integer $emailID
     * @param bool    $success
     * @return boolean
     */
    public static function markAsSent(int $emailID, bool $success): bool {
        return self::getSchema()->edit($emailID, [
            "sentSuccess" => $success ? 1 : 0,
            "sentTime"    => time(),
        ]);
    }
}
