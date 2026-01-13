<?php
namespace Framework\Core;

use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Access Role
 */
class AccessRole implements DiscoveryBuilder {

    private static int   $level  = -1;

    /** @var array<string,string[]> */
    private static array $groups = [];

    /** @var array<string,integer> */
    private static array $roles  = [];


    /**
     * Registers an Access Role
     * @param string $roleName
     * @param string $groupName
     * @return boolean
     */
    public static function register(string $roleName, string $groupName, int $level = -1): bool {
        if ($level >= 0) {
            self::$level = $level;
        } else {
            self::$level += 1;
        }

        self::$roles[$roleName] = self::$level;

        if (!isset(self::$groups[$groupName])) {
            self::$groups[$groupName] = [];
        }
        if (!Arrays::contains(self::$groups[$groupName], $roleName)) {
            self::$groups[$groupName][] = $roleName;
        }
        return true;
    }



    /**
     * Generates the code
     * @return integer
     */
    public static function generateCode(): int {
        if (count(self::$roles) === 0) {
            DiscoveryConfig::loadDefault("access");
        }

        $roleList  = self::getAccesses(self::$groups, self::$roles);
        $maxLength = self::alignNames($roleList);

        // Builds the code
        return Builder::generateCode("Access", [
            "roles"   => $roleList,
            "groups"  => self::getGroups(self::$groups),
            "default" => Strings::padRight("default", $maxLength + 6),
            "total"   => count(self::$roles),
        ]);
    }

    /**
     * Destroys the Code
     * @return integer
     */
    public static function destroyCode(): int {
        return 1;
    }

    /**
     * Returns the Access Roles for the generator
     * @param array<string,array<string>> $groups
     * @param array<string,integer>       $roles
     * @return array{addSpace:boolean,group:string,name:string,constant:string,level:integer}[]
     */
    private static function getAccesses(array $groups, array $roles): array {
        $result = [];
        foreach ($groups as $groupName => $accessList) {
            $addSpace = true;
            foreach ($accessList as $accessName) {
                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $groupName,
                    "name"     => $accessName,
                    "constant" => "",
                    "level"    => $roles[$accessName] ?? 0,
                ];
                $addSpace  = false;
            }
        }
        return $result;
    }

    /**
     * Returns the Access Groups for the generator
     * @param array<string,array<string>> $groups
     * @return array<string,string>[]
     */
    private static function getGroups(array $groups): array {
        $result = [];
        foreach ($groups as $groupName => $roles) {
            $result[] = [
                "name"   => $groupName,
                "roles"  => Strings::join($roles, ", "),
                "values" => "self::" . Strings::join($roles, ", self::"),
            ];
        }
        return $result;
    }

    /**
     * Aligns the List Names
     * @param array{addSpace:boolean,group:string,name:string,constant:string,level:integer}[] $list
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
