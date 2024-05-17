<?php
namespace Framework\System;

use Framework\Framework;
use Framework\Utils\Strings;

/**
 * The Status Code
 */
class StatusCode {

    const Active   = "Active";
    const Inactive = "Inactive";



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $data    = Framework::loadJSON(Framework::DataDir, Framework::StatusData, true);
        $appData = Framework::loadData(Framework::StatusData);
        if (!empty($appData)) {
            $data = array_merge($data, $appData);
        }

        return [
            "statuses" => self::getStatues($data),
            "groups"   => self::getGroups($data),
        ];
    }

    /**
     * Generates the Statues data
     * @param array{} $data
     * @return array{}[]
     */
    private static function getStatues(array $data): array {
        $result    = [];
        $used      = [];
        $maxLength = 0;

        foreach ($data as $group => $values) {
            $addSpace = true;
            foreach ($values as $name) {
                if (!empty($used[$name])) {
                    continue;
                }

                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $group,
                    "statuses" => Strings::join($values, ", "),
                    "name"     => $name,
                ];
                $used[$name] = true;
                $maxLength   = max($maxLength, Strings::length($name));
                $addSpace    = false;
            }
        }
        foreach ($result as $index => $status) {
            $result[$index]["constant"] = Strings::padRight($status["name"], $maxLength);
        }

        return $result;
    }

    /**
     * Generates the Groups data
     * @param array{} $data
     * @return array{}[]
     */
    private static function getGroups(array $data): array {
        $result = [];
        foreach ($data as $name => $values) {
            $result[] = [
                "name"     => $name,
                "values"   => "self::" . Strings::join($values, ", self::"),
                "statuses" => Strings::join($values, ", "),

            ];
        }

        return $result;
    }
}
