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

        $values = $data["values"];
        $groups = $data["groups"];
        if (!empty($appData)) {
            $values = array_merge($data["values"], $appData["values"]);
            $groups = array_merge($data["groups"], $appData["groups"]);
        }

        return [
            "statuses" => self::getStatues($values),
            "groups"   => self::getGroups($groups),
        ];
    }

    /**
     * Generates the Statues data
     * @param array{} $values
     * @return array{}[]
     */
    private static function getStatues(array $values): array {
        $result    = [];
        $maxLength = 0;

        foreach ($values as $name => $color) {
            $result[] = [
                "name"  => $name,
                "color" => $color,
            ];
            $maxLength = max($maxLength, Strings::length($name));
        }
        foreach ($result as $index => $status) {
            $result[$index]["constant"] = Strings::padRight($status["name"], $maxLength);
        }

        return $result;
    }

    /**
     * Generates the Groups data
     * @param array{} $groups
     * @return array{}[]
     */
    private static function getGroups(array $groups): array {
        $result = [];
        foreach ($groups as $name => $values) {
            $result[] = [
                "name"     => $name,
                "values"   => "self::" . Strings::join($values, ", self::"),
                "statuses" => Strings::join($values, ", "),
            ];
        }
        return $result;
    }
}
