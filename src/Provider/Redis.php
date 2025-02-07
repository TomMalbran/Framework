<?php
namespace Framework\Provider;

use Framework\System\Config;
use Framework\Utils\JSON;

use Redis as RedisClient;
use Exception;

/**
 * The Redis Provider
 */
class Redis {

    private static bool        $loaded   = false;
    private static bool        $disabled = false;
    private static RedisClient $client;


    /**
     * Creates the Redis Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$disabled) {
            return false;
        }
        if (self::$loaded) {
            return true;
        }

        if (!Config::isRedisActive()) {
            self::$disabled = true;
            return false;
        }

        if (!extension_loaded("redis")) {
            self::$disabled = true;
            return false;
        }

        self::$client = new RedisClient();
        try {
            self::$client->connect("127.0.0.1", 6379);
        } catch (Exception $e) {
            self::$disabled = true;
            return false;
        }

        self::$loaded = true;
        return true;
    }



    /**
     * Sets a Key
     * @param string  $module
     * @param integer $id
     * @param mixed   $data
     * @return boolean
     */
    public static function set(string $module, int $id, mixed $data): bool {
        if (!self::load()) {
            return false;
        }

        try {
            $key   = "$module-$id";
            $value = JSON::encode($data);
            return self::$client->set($key, $value);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets a Key
     * @param string  $module
     * @param integer $id
     * @return mixed
     */
    public static function get(string $module, int $id): mixed {
        if (!self::load()) {
            return null;
        }

        try {
            $key   = "$module-$id";
            $value = self::$client->get($key);
            return JSON::decode($value, true);
        } catch (Exception $e) {
            return null;
        }
    }
}
