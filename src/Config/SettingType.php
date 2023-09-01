<?php
namespace Framework\Config;

use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Setting Types used by the System
 */
class SettingType {

    const General = 0;
    const Binary  = 1;
    const JSON    = 2;



    /**
     * Returns the Setting Type based on the value
     * @param mixed $value
     * @return integer
     */
    public static function get(mixed $value): int {
        if (Arrays::isArray($value)) {
            return self::JSON;
        }
        if (gettype($value) == "boolean") {
            return self::Binary;
        }
        return self::General;
    }

    /**
     * Parses a Settings Value from the Database
     * @param Model|array{} $data
     * @return mixed
     */
    public static function parseValue(Model|array $data): mixed {
        return match ($data["type"]) {
            self::Binary => !empty($data["value"]),
            self::JSON   => JSON::decode($data["value"]),
            default      => $data["value"],
        };
    }

    /**
     * Encodes the Settings Value for the Database
     * @param integer $type
     * @param mixed   $value
     * @return mixed
     */
    public static function encodeValue(int $type, mixed $value): mixed {
        return match ($type) {
            self::Binary => !empty($value) ? 1 : 0,
            self::JSON   => JSON::encode($value),
            default      => $value,
        };
    }
}
