<?php
namespace Framework\System;

use Framework\Framework;
use Framework\Utils\Strings;
use Framework\Utils\Arrays;

/**
 * The Access Code
 */
class AccessCode {

    const General = "General";
    const Admin   = "Admin";
    const API     = "API";


    private static bool $loaded = false;

    /** @var array<string,string[]> */
    private static array $groups = [];

    /** @var array<string,integer> */
    private static array $accesses = [];



    /**
     * Loads the Access Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        $data = Framework::loadData(Framework::AccessData);

        // Store the groups and accesses
        foreach ($data as $groupName => $accessData) {
            self::$groups[$groupName] = [];
            foreach ($accessData as $accessName => $accessLevel) {
                self::$groups[$groupName][]  = $accessName;
                self::$accesses[$accessName] = $accessLevel;
            }
        }
        return true;
    }



    /**
     * Returns the Access Level from an Access Name
     * @param string $accessName
     * @return integer
     */
    public static function getLevel(string $accessName): int {
        self::load();
        if (isset(self::$accesses[$accessName])) {
            return self::$accesses[$accessName];
        }
        return -1;
    }

    /**
     * Returns true if the Access is inside the given Group
     * @param string $groupName
     * @param string $accessName
     * @return boolean
     */
    public static function inGroup(string $groupName, string $accessName): bool {
        self::load();
        if (!isset(self::$groups[$groupName])) {
            return false;
        }
        return Arrays::contains(self::$groups[$groupName], $accessName);
    }



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        self::load();
        if (empty(self::$accesses)) {
            return [];
        }

        return [
            "accesses" => self::getAccesses(),
            "groups"   => self::getGroups(),
        ];
    }

    /**
     * Returns the Access Accesses for the generator
     * @return mixed[]
     */
    private static function getAccesses(): array {
        $result    = [];
        $maxLength = 0;

        foreach (self::$groups as $groupName => $accesses) {
            $addSpace = true;
            foreach ($accesses as $accessName) {
                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $groupName,
                    "name"     => $accessName,
                    "level"    => self::$accesses[$accessName],
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
     * @return mixed[]
     */
    private static function getGroups(): array {
        $result = [];
        foreach (self::$groups as $groupName => $accesses) {
            $result[] = [
                "name"     => $groupName,
                "accesses" => Strings::join($accesses, ", "),
                "values"   => "self::" . Strings::join($accesses, ", self::"),
            ];
        }
        return $result;
    }
}
