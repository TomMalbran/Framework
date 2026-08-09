<?php
namespace Tests\Core;

use Framework\Core\AccessRole;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\Package;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AccessRoleTest extends TestCase {
    use TestHelpers;

    protected function setUp(): void {
        $this->resetState();
    }

    protected function tearDown(): void {
        $this->resetState();
    }

    private function resetState(): void {
        $this->setPrivateStaticProperty(AccessRole::class, "level", -1);
        $this->setPrivateStaticProperty(AccessRole::class, "groups", []);
        $this->setPrivateStaticProperty(AccessRole::class, "roles", []);
    }


    #[DataProvider("providerRegisterLevels")]
    public function testRegisterLevels(array $register, array $expected): void {
        foreach ($register as [ $roleName, $groupName, $level ]) {
            AccessRole::register($roleName, $groupName, $level);
        }

        $roles = $this->getPrivateStaticProperty(AccessRole::class, "roles");
        $this->assertSame($expected, $roles);
    }

    public static function providerRegisterLevels(): array {
        return [
            "auto_increments" => [
                [ [ "Admin", "General", -1 ], [ "Editor", "General", -1 ] ],
                [ "Admin" => 0, "Editor" => 1 ],
            ],
            "explicit_level" => [
                [ [ "Admin", "General", 5 ], [ "Editor", "General", -1 ] ],
                [ "Admin" => 5, "Editor" => 6 ],
            ],
            "across_groups" => [
                [ [ "Admin", "General", -1 ], [ "Viewer", "Public", -1 ] ],
                [ "Admin" => 0, "Viewer" => 1 ],
            ],
        ];
    }


    public function testRegisterSkipsDuplicatesInGroup(): void {
        AccessRole::register("Admin", "General");
        AccessRole::register("Admin", "General");

        $groups = $this->getPrivateStaticProperty(AccessRole::class, "groups");
        $this->assertSame([ "General" => [ "Admin" ] ], $groups);
    }


    public function testCollectRoles(): void {
        AccessRole::register("Admin", "General");
        AccessRole::register("Editor", "General");
        AccessRole::register("Viewer", "Public");

        $result = AccessRole::collectRoles();

        $this->assertSame(3, $result["total"]);
        $this->assertSame([
            [ "addSpace" => true,  "group" => "General", "name" => "Admin",  "constant" => "Admin ", "level" => 0 ],
            [ "addSpace" => false, "group" => "General", "name" => "Editor", "constant" => "Editor", "level" => 1 ],
            [ "addSpace" => true,  "group" => "Public",  "name" => "Viewer", "constant" => "Viewer", "level" => 2 ],
        ], $result["roles"]);
    }


    public function testCollectRolesGroups(): void {
        AccessRole::register("Admin", "General");
        AccessRole::register("Editor", "General");

        $result = AccessRole::collectRoles();

        $this->assertSame([
            [ "name" => "General", "roles" => "Admin, Editor", "values" => "self::Admin, self::Editor" ],
        ], $result["groups"]);
    }


    #[DataProvider("providerCollectRolesDefault")]
    public function testCollectRolesDefault(array $names, string $expected): void {
        foreach ($names as $name) {
            AccessRole::register($name, "General");
        }

        $this->assertSame($expected, AccessRole::collectRoles()["default"]);
    }

    public static function providerCollectRolesDefault(): array {
        return [
            // "default" (7 chars) padded to the longest role name + 6
            "short_name" => [ [ "Admin" ], str_pad("default", 5 + 6) ],
            "long_name"  => [ [ "Administrator" ], str_pad("default", 13 + 6) ],
        ];
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, AccessRole::destroyCode());
    }


    public function testTheDefaultRolesAreFound(): void {
        // With none registered, the roles are read from the config file
        $data = AccessRole::collectRoles();

        $this->assertGreaterThan(0, $data["total"], "no roles came from the config");
    }

    public function testTheConfigIsNamedAsItIsAskedFor(): void {
        // loadDefault builds the file name, and a case-insensitive disk finds it
        // whatever the case, so the mismatch only shows on Linux. Compared here
        // against the real directory entry, so it fails on any machine.
        $configPath = Package::getBasePath(Package::ConfigDir);
        $fileNames  = Storage::getFilesInDir($configPath);

        $this->assertContains("Access" . DiscoveryConfig::Extension, $fileNames);
    }
}
