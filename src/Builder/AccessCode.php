<?php
namespace Framework\Builder;

use Framework\Framework;
use Framework\Utils\Strings;

/**
 * The Access Code
 */
class AccessCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $data     = Framework::loadData(Framework::AccessData);
        $groups   = [];
        $accesses = [];

        foreach ($data as $groupName => $accessData) {
            $groups[$groupName] = [];
            foreach ($accessData as $accessName => $accessLevel) {
                $groups[$groupName][]  = $accessName;
                $accesses[$accessName] = $accessLevel;
            }
        }

        return [
            "accesses" => self::getAccesses($groups, $accesses),
            "groups"   => self::getGroups($groups),
        ];
    }

    /**
     * Returns the Access Accesses for the generator
     * @param array<string,array<string>> $groups
     * @param array<string,integer>       $accesses
     * @return array{}[]
     */
    private static function getAccesses(array $groups, array $accesses): array {
        $result    = [];
        $maxLength = 0;

        foreach ($groups as $groupName => $accessList) {
            $addSpace = true;
            foreach ($accessList as $accessName) {
                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $groupName,
                    "name"     => $accessName,
                    "level"    => $accesses[$accessName],
                ];
                $maxLength = max($maxLength, Strings::length($accessName));
                $addSpace  = false;
            }
        }
        foreach ($result as $index => $access) {
            $result[$index]["constant"] = Strings::padRight($access["name"], $maxLength);
        }

        return $result;
    }

    /**
     * Returns the Access Groups for the generator
     * @param array<string,array<string>> $groups
     * @return array{}[]
     */
    private static function getGroups(array $groups): array {
        $result = [];
        foreach ($groups as $groupName => $accesses) {
            $result[] = [
                "name"     => $groupName,
                "accesses" => Strings::join($accesses, ", "),
                "values"   => "self::" . Strings::join($accesses, ", self::"),
            ];
        }
        return $result;
    }
}
