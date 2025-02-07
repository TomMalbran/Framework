<?php
namespace Framework\Builder;

use Framework\Framework;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Status Code
 */
class StatusCode {

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
            $values = Arrays::merge($data["values"], $appData["values"]);
            $groups = Arrays::merge($data["groups"], $appData["groups"]);
        }

        $statusList = self::getStatues($values);
        $maxLength  = self::alignNames($statusList);

        return [
            "statuses" => $statusList,
            "groups"   => self::getGroups($groups),
            "default"  => Strings::padRight("default", $maxLength + 6),
        ];
    }

    /**
     * Generates the Statues data
     * @param array{} $values
     * @return array{}[]
     */
    private static function getStatues(array $values): array {
        $result = [];
        foreach ($values as $name => $color) {
            $result[] = [
                "name"  => $name,
                "color" => $color,
            ];
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
                "statuses" => Strings::join($values, ", "),
                "values"   => "self::" . Strings::join($values, ", self::"),
            ];
        }
        return $result;
    }

    /**
     * Aligns the List Names
     * @param array{} $list
     * @return integer
     */
    private static function alignNames(array &$list): int {
        $maxLength = 0;
        foreach ($list as $elem) {
            $maxLength = max($maxLength, Strings::length($elem["name"]));
        }
        foreach ($list as $index => $elem) {
            $list[$index]["constant"] = Strings::padRight($elem["name"], $maxLength);
        }
        return $maxLength;
    }
}
