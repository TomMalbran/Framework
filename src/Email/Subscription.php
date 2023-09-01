<?php
namespace Framework\Email;

use Framework\Utils\Enum;

/**
 * The Subscriptions used by the System
 */
class Subscription extends Enum {

    const None          = 0;
    const Subscribed    = 1;
    const Unsubscribed  = 2;
    const Cleaned       = 3;
    const Pending       = 4;
    const Transactional = 5;
    const Archived      = 6;



    /**
     * Returns the Subscription as an int
     * @param string $status
     * @return integer
     */
    public static function fromString(string $status): int {
        return match ($status) {
            "subscribed"    => self::Subscribed,
            "unsubscribed"  => self::Unsubscribed,
            "cleaned"       => self::Cleaned,
            "pending"       => self::Pending,
            "transactional" => self::Transactional,
            "archived"      => self::Archived,
            default         => self::None,
        };
    }
}
