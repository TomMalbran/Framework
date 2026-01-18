<?php
namespace Framework\Core;

use Framework\Discovery\DiscoveryMigration;
use Framework\Core\SettingConfig;
use Framework\Core\Schema\SettingsSchema;
use Framework\Core\Schema\SettingsEntity;
use Framework\Core\Schema\SettingsColumn;
use Framework\Core\Schema\SettingsQuery;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Setting Data
 */
class SettingData extends SettingsSchema implements DiscoveryMigration {

    /**
     * Returns a single Setting
     * @param string $section
     * @param string $variable
     * @return string
     */
    public static function get(string $section, string $variable): string {
        $query = new SettingsQuery();
        $query->section->equal($section);
        $query->variable->equal($variable);

        $result = self::getEntityValue($query, SettingsColumn::Value);
        return Strings::toString($result);
    }

    /**
     * Sets a single Setting
     * @param string $section
     * @param string $variable
     * @param string $value
     * @return bool
     */
    public static function set(string $section, string $variable, string $value): bool {
        $query = new SettingsQuery();
        $query->section->equal($section);
        $query->variable->equal($variable);

        $elem = self::getEntity($query);
        if ($elem->isEmpty()) {
            return false;
        }

        $result = self::replaceEntity(
            section:      $section,
            variable:     $variable,
            value:        $value,
            variableType: $elem->variableType,
        );
        return $result > 0;
    }

    /**
     * Returns a Core Setting
     * @param string $variable
     * @return int
     */
    public static function getCore(string $variable): int {
        if (!self::tableExists()) {
            return 0;
        }

        $result = self::get(SettingConfig::Core, $variable);
        return Numbers::toInt($result);
    }

    /**
     * Sets a Core Preference
     * @param string $variable
     * @param int    $value
     * @return bool
     */
    public static function setCore(string $variable, int $value): bool {
        if (!self::tableExists()) {
            return false;
        }

        return self::replaceEntity(
            section:      SettingConfig::Core,
            variable:     $variable,
            value:        (string)$value,
            variableType: VariableType::Integer->name,
        );
    }



    /**
     * Returns all the Settings
     * @param string|null $section  Optional.
     * @param bool        $asObject Optional.
     * @return array{}|object
     */
    public static function getAll(?string $section = null, bool $asObject = false): array|object {
        $query = new SettingsQuery();
        if ($section !== null && $section !== "") {
            $query->section->equal($section);
        }

        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            if (!isset($result[$elem->section])) {
                $result[$elem->section] = [];
            }
            $result[$elem->section][$elem->variable] = $elem->value;
        }

        if ($section !== null && $section !== "") {
            $result = $result[$section] ?? [];
        }
        return $asObject ? (object)$result : $result;
    }

    /**
     * Saves all the Settings
     * @param array<string,string> $data
     * @return bool
     */
    public static function saveAll(array $data): bool {
        $list = self::getEntityList();

        foreach ($list as $elem) {
            $variable = "{$elem->section}-{$elem->variable}";
            if (!isset($data[$variable])) {
                continue;
            }

            $variableType = VariableType::fromValue($elem->variableType);
            self::replaceEntity(
                section:      $elem->section,
                variable:     $elem->variable,
                value:        VariableType::encodeValue($variableType, $data[$variable]),
                variableType: $elem->variableType,
            );
        }
        return true;
    }

    /**
     * Saves the Settings from the given Section
     * @param string               $section
     * @param array<string,string> $data
     * @return bool
     */
    public static function saveSection(string $section, array $data): bool {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields["$section-$key"] = $value;
        }
        return self::saveAll($fields);
    }




    /**
     * Migrates the Settings data
     * @return bool
     */
    #[\Override]
    public static function migrateData(): bool {
        $query = new SettingsQuery();
        $query->section->notEqual(SettingConfig::Core);
        $list = self::getEntityList($query);

        $variables = [];
        $adds      = [];
        $renames   = [];
        $modifies  = [];
        $deletes   = [];

        // Add/Update Settings
        foreach (SettingConfig::getSettings() as $setting) {
            $section      = $setting["section"];
            $variable     = $setting["variable"];
            $variableType = $setting["variableType"];
            $value        = $setting["value"];
            $found        = false;

            foreach ($list as $elem) {
                if ($elem->section === $section && $elem->variable === $variable) {
                    if ($elem->variableType !== $variableType->name) {
                        $modifies[] = (object)[
                            "section"      => $elem->section,
                            "variable"     => $elem->variable,
                            "variableType" => $variableType->name,
                        ];
                    }
                    $found = true;
                    break;
                }

                if (Strings::isEqual($elem->section, $section) && Strings::isEqual($elem->variable, $variable)) {
                    $renames[] = (object)[
                        "section"  => $elem->section,
                        "variable" => $elem->variable,
                        "fields"   => [
                            "section"      => $section,
                            "variable"     => $variable,
                            "variableType" => $variableType->name,
                        ],
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $variables[] = "{$section}_{$variable}";
                $fields      = [
                    "section"      => $section,
                    "variable"     => $variable,
                    "value"        => VariableType::encodeValue($variableType, $value),
                    "variableType" => $variableType->name,
                    "modifiedTime" => time(),
                ];
                $list[] = new SettingsEntity($fields);
                $adds[] = $fields;
            }
        }

        // Remove Settings
        foreach ($list as $elem) {
            $found = false;
            foreach (SettingConfig::getSettings() as $setting) {
                if (Strings::isEqual($elem->section, $setting["section"]) &&
                    Strings::isEqual($elem->variable, $setting["variable"])
                ) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $deletes[] = (object)[
                    "section"  => $elem->section,
                    "variable" => $elem->variable,
                ];
            }
        }

        // Process the SQL
        $didUpdate = false;
        if (count($adds) > 0) {
            print("- Added " . count($adds) . " settings\n");
            print("    " . Strings::join($variables, ", ") . "\n");
            foreach ($adds as $add) {
                self::replaceEntity(
                    section:      $add["section"],
                    variable:     $add["variable"],
                    value:        $add["value"],
                    variableType: $add["variableType"],
                );
            }
            $didUpdate = true;
        }

        if (count($renames) > 0) {
            print("- Renamed " . count($renames) . " settings\n");
            foreach ($renames as $rename) {
                $query = new SettingsQuery();
                $query->section->equal($rename->section);
                $query->variable->equal($rename->variable);
                self::editEntity($query, ...$rename->fields);
            }
            $didUpdate = true;
        }

        if (count($modifies) > 0) {
            print("- Modified " . count($modifies) . " settings\n");
            foreach ($modifies as $modify) {
                $query = new SettingsQuery();
                $query->section->equal($modify->section);
                $query->variable->equal($modify->variable);
                self::editEntity($query, variableType: $modify->variableType);
            }
            $didUpdate = true;
        }

        if (count($deletes) > 0) {
            print("- Deleted " . count($deletes) . " settings\n");
            $variables = [];
            foreach ($deletes as $delete) {
                $query = new SettingsQuery();
                $query->section->equal($delete->section);
                $query->variable->equal($delete->variable);

                self::removeEntity($query);
                $variables[] = "{$delete->section}_{$delete->variable}";
            }
            print("    " . Strings::join($variables, ", ") . "\n");
            $didUpdate = true;
        }

        if (!$didUpdate) {
            print("- No settings updated\n");
            return false;
        }
        return true;
    }
}
