<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Status Code
 */
class StatusCode {

    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        $frameData = Discovery::loadFrameData(DataFile::Status);
        $appData   = Discovery::loadData(DataFile::Status);

        $values = $frameData["values"];
        $groups = $frameData["groups"];

        if (!empty($appData)) {
            $values = Arrays::merge($frameData["values"], $appData["values"]);
            $groups = Arrays::merge($frameData["groups"], $appData["groups"]);
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
     * @param array<string,mixed> $values
     * @return array{name:string,color:string}[]
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
     * @param array<string,mixed> $groups
     * @return array{name:string,statuses:string,values:string}[]
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
     * @param array{name:string,color:string,constant:string}[] $list
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
