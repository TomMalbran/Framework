<?php
namespace Framework\Core;

use Framework\Framework;
use Framework\Core\VariableType;
use Framework\Database\Query;
use Framework\Utils\Strings;
use Framework\Schema\SettingsSchema;
use Framework\Schema\SettingsEntity;
use Framework\Schema\SettingsColumn;

/**
 * The Settings
 */
class Settings extends SettingsSchema {

    const Core    = "Core";
    const General = "General";



    /**
     * Returns a single Setting
     * @param string $section
     * @param string $variable
     * @return string
     */
    public static function get(string $section, string $variable): string {
        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);
        return self::getEntityValue($query, SettingsColumn::Value);
    }

    /**
     * Sets a single Setting
     * @param string $section
     * @param string $variable
     * @param string $value
     * @return boolean
     */
    public static function set(string $section, string $variable, string $value): bool {
        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);

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
     * @return integer
     */
    public static function getCore(string $variable): int {
        if (!self::tableExists()) {
            return 0;
        }

        $result = self::get(self::Core, $variable);
        return !empty($result) ? $result : 0;
    }

    /**
     * Sets a Core Preference
     * @param string  $variable
     * @param integer $value
     * @return boolean
     */
    public static function setCore(string $variable, int $value): bool {
        if (!self::tableExists()) {
            return false;
        }

        return self::replaceEntity(
            section:      self::Core,
            variable:     $variable,
            value:        $value,
            variableType: VariableType::Integer,
        );
    }



    /**
     * Returns all the Settings
     * @param string|null $section  Optional.
     * @param boolean     $asObject Optional.
     * @return array{}|object
     */
    public static function getAll(?string $section = null, bool $asObject = false): array|object {
        $query  = Query::createIf("section", "=", $section);
        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            if (empty($result[$elem->section])) {
                $result[$elem->section] = [];
            }
            $result[$elem->section][$elem->variable] = $elem->value;
        }

        if (!empty($section)) {
            $result = !empty($result[$section]) ? $result[$section] : [];
        }
        return $asObject ? (object)$result : $result;
    }

    /**
     * Saves all the Settings
     * @param array{} $data
     * @return boolean
     */
    public static function saveAll(array $data): bool {
        $list = self::getEntityList();

        foreach ($list as $elem) {
            $variable = "{$elem->section}-{$elem->variable}";
            if (!isset($data[$variable])) {
                continue;
            }

            self::replaceEntity(
                section:      $elem->section,
                variable:     $elem->variable,
                value:        VariableType::encodeValue($elem->variableType, $data[$variable]),
                variableType: $elem->variableType,
            );
        }
        return true;
    }

    /**
     * Saves the Settings from the given Section
     * @param string  $section
     * @param array{} $data
     * @return boolean
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
     * @return boolean
     */
    public static function migrateData(): bool {
        $settings  = Framework::loadData(Framework::SettingsData);
        $query     = Query::create("section", "<>", self::Core);
        $list      = self::getEntityList($query);

        $variables = [];
        $adds      = [];
        $renames   = [];
        $modifies  = [];
        $deletes   = [];

        // Add/Update Settings
        foreach ($settings as $section => $data) {
            foreach ($data as $variable => $value) {
                $found        = false;
                $variableType = VariableType::get($value);

                foreach ($list as $elem) {
                    if ($elem->section === $section && $elem->variable === $variable) {
                        if ($elem->variableType !== $variableType) {
                            $modifies[] = [
                                "section"      => $elem->section,
                                "variable"     => $elem->variable,
                                "variableType" => $variableType,
                            ];
                        }
                        $found = true;
                        break;
                    }

                    if (Strings::isEqual($elem->section, $section) && Strings::isEqual($elem->variable, $variable)) {
                        $renames[] = [
                            "section"  => $elem->section,
                            "variable" => $elem->variable,
                            "fields"   => [
                                "section"      => $section,
                                "variable"     => $variable,
                                "variableType" => $variableType,
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
                        "variableType" => $variableType,
                        "modifiedTime" => time(),
                    ];
                    $list[] = new SettingsEntity($fields);
                    $adds[] = $fields;
                }
            }
        }

        // Remove Settings
        foreach ($list as $elem) {
            $found = false;
            foreach ($settings as $section => $data) {
                foreach ($data as $variable => $value) {
                    if (Strings::isEqual($elem->section, $section) &&
                        Strings::isEqual($elem->variable, $variable)
                    ) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                $deletes[] = [ $elem->section, $elem->variable ];
            }
        }

        // Process the SQL
        $didUpdate = false;
        if (!empty($adds)) {
            print("<br>Added <i>" . count($adds) . " settings</i><br>");
            print(Strings::join($variables, ", ") . "<br>");
            self::batchEntities($adds);
            $didUpdate = true;
        }

        if (!empty($renames)) {
            print("<br>Renamed <i>" . count($renames) . " settings</i><br>");
            foreach ($renames as $rename) {
                $query = Query::create("section", "=", $rename["section"]);
                $query->add("variable", "=", $rename["variable"]);
                self::editEntity($query, ...$rename["fields"]);
            }
            $didUpdate = true;
        }

        if (!empty($modifies)) {
            print("<br>Modified <i>" . count($modifies) . " settings</i><br>");
            foreach ($modifies as $modify) {
                $query = Query::create("section", "=", $modify["section"]);
                $query->add("variable", "=", $modify["variable"]);
                self::editEntity($query, variableType: $modify["variableType"]);
            }
            $didUpdate = true;
        }

        if (!empty($deletes)) {
            print("<br>Deleted <i>" . count($deletes) . " settings</i><br>");
            $variables = [];
            foreach ($deletes as $delete) {
                $query = Query::create("section", "=", $delete[0]);
                $query->add("variable", "=", $delete[1]);
                self::removeEntity($query);
                $variables[] = $delete[0] . "_" . $delete[1];
            }
            print(Strings::join($variables, ", ") . "<br>");
            $didUpdate = true;
        }

        if (!$didUpdate) {
            print("<br>No <i>settings</i> updated<br>");
            return false;
        }
        return true;
    }
}
