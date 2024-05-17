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

    /** @var array<string,integer[]> */
    private static array $groupLevels = [];

    /** @var array<string,integer> */
    private static array $levels = [];



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

        // Store the groups and levels
        foreach ($data as $groupName => $accessData) {
            self::$groups[$groupName] = [];
            foreach ($accessData as $accessName => $accessLevel) {
                self::$groups[$groupName][]      = $accessName;
                self::$groupLevels[$groupName][] = $accessLevel;
                self::$levels[$accessName]       = $accessLevel;
            }
        }
        // foreach ($data as $groupName => $accessData) {
        //     $gName = Strings::toLowerCase($groupName);
        //     self::$groups[$gName] = [];
        //     foreach ($accessData as $accessName => $accessLevel) {
        //         $aName = Strings::toLowerCase($accessName);
        //         self::$groups[$gName][] = $accessLevel;
        //         self::$levels[$aName]   = $accessLevel;
        //     }
        // }
        return true;
    }



    /**
     * Returns the Access Level from an Access Name
     * @param string $accessName
     * @return integer
     */
    public static function getLevel(string $accessName): int {
        self::load();
        if (isset(self::$levels[$accessName])) {
            return self::$levels[$accessName];
        }
        return -1;
    }

    /**
     * Returns the Access Levels inside the Admin Group
     * @param string  $groupName
     * @param integer $level
     * @return boolean
     */
    public static function inGroup(string $groupName, int $level): bool {
        self::load();
        if (!isset(self::$groupLevels[$groupName])) {
            return false;
        }
        $levels = self::$groupLevels[$groupName];
        return Arrays::contains($levels, $level);
    }



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        self::load();
        if (empty(self::$levels)) {
            return [];
        }

        return [
            "levels" => self::getLevels(),
            "groups" => self::getGroups(),
        ];
    }

    /**
     * Returns the Access Levels for the generator
     * @return mixed[]
     */
    private static function getLevels(): array {
        $result    = [];
        $maxLength = 0;

        foreach (self::$groups as $groupName => $accesses) {
            $addSpace = true;
            foreach ($accesses as $accessName) {
                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $groupName,
                    "name"     => $accessName,
                    "level"    => self::$levels[$accessName],
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
            $levels = [];
            foreach ($accesses as $accessName) {
                $levels[] = self::$levels[$accessName];
            }

            $result[] = [
                "name"     => $groupName,
                "accesses" => Strings::join($accesses, ", "),
                "levels"   => Strings::join($levels, ", "),
            ];
        }
        return $result;
    }
}
