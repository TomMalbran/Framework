<?php
namespace Framework\Core;

use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Access Role
 * @phpstan-type AccessData array{
 *   addSpace: bool,
 *   group:    string,
 *   name:     string,
 *   constant: string,
 *   level:    int,
 * }
 * @phpstan-type AccessGroupData array{
 *   name:   string,
 *   roles:  string,
 *   values: string,
 * }
 * @phpstan-type AccessTokenData array{
 *   name:     string,
 *   constant: string,
 *   key:      string,
 * }
 * @phpstan-type AccessResult array{
 *   roles:     list<AccessData>,
 *   groups:    list<AccessGroupData>,
 *   tokens:    list<AccessTokenData>,
 *   tokenList: string,
 *   default:   string,
 *   total:     int,
 * }
 */
class AccessRole implements DiscoveryBuilder {

    private static int $level = -1;

    /** @var array<string,list<string>> */
    private static array $groups = [];

    /** @var array<string,int> */
    private static array $roles = [];

    /** @var array<string,string> */
    private static array $tokens = [];


    /**
     * Registers an Access Role
     * @param string $roleName
     * @param string $groupName
     * @param int    $level     Optional.
     * @param string $tokenKey  Optional.
     * @return void
     */
    public static function register(
        string $roleName,
        string $groupName,
        int $level = -1,
        string $tokenKey = "",
    ): void {
        if ($level >= 0) {
            self::$level = $level;
        } else {
            self::$level += 1;
        }

        self::$roles[$roleName] = self::$level;
        if ($tokenKey !== "") {
            self::$tokens[$roleName] = Strings::toCamelCase($tokenKey);
        }

        if (!isset(self::$groups[$groupName])) {
            self::$groups[$groupName] = [];
        }
        if (!Arrays::contains(self::$groups[$groupName], $roleName)) {
            self::$groups[$groupName][] = $roleName;
        }
    }



    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    #[NotTested("It generates a file")]
    public static function generateCode(): int {
        $data = self::collectRoles();
        return Builder::generateCode("Access", $data);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }



    /**
     * Collects the Access Roles used to generate the code
     * @return AccessResult
     */
    public static function collectRoles(): array {
        if (count(self::$roles) === 0) {
            DiscoveryConfig::loadDefault("Access");
        }

        $roleList  = self::getAccesses(self::$groups, self::$roles);
        $maxLength = self::alignNames($roleList);
        $tokenList = self::getTokens($roleList, self::$tokens);

        $values = [];
        foreach ($tokenList as $token) {
            $values[] = "self::{$token["name"]}";
        }

        return [
            "roles"     => $roleList,
            "groups"    => self::getGroups(self::$groups),
            "tokens"    => $tokenList,
            "tokenList" => count($values) > 0 ? "[ " . Strings::join($values, ", ") . " ]" : "[]",
            "default"   => Strings::padRight("default", $maxLength + 6),
            "total"     => count(self::$roles),
        ];
    }

    /**
     * Returns the Access Roles that a Token grants, in the order they were added
     * @param list<AccessData>     $roleList
     * @param array<string,string> $tokens
     * @return list<AccessTokenData>
     */
    private static function getTokens(array $roleList, array $tokens): array {
        $result = [];
        foreach ($roleList as $role) {
            $tokenKey = $tokens[$role["name"]] ?? "";
            if ($tokenKey !== "") {
                $result[] = [
                    "name"     => $role["name"],
                    "constant" => $role["constant"],
                    "key"      => $tokenKey,
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the Access Roles for the generator
     * @param array<string,array<string>> $groups
     * @param array<string,int>           $roles
     * @return list<AccessData>
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
                $addSpace = false;
            }
        }
        return $result;
    }

    /**
     * Returns the Access Groups for the generator
     * @param array<string,array<string>> $groups
     * @return list<AccessGroupData>
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
     * @param list<AccessData> $list
     * @return int
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
