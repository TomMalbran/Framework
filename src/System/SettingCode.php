<?php
namespace Framework\System;

use Framework\Framework;
use Framework\System\VariableType;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Database;
use Framework\Schema\Query;
use Framework\Utils\Strings;

/**
 * The Setting Code
 */
class SettingCode {

    const Core    = "Core";
    const General = "General";



    /**
     * Loads the Settings Schemas
     * @return Schema
     */
    private static function schema(): Schema {
        return Factory::getSchema("Settings");
    }



    /**
     * Returns a single Setting
     * @param string $section
     * @param string $variable
     * @return string
     */
    public static function get(string $section, string $variable): string {
        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);
        return self::schema()->getValue($query, "value");
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
        $model = self::schema()->getOne($query);
        if ($model->isEmpty()) {
            return false;
        }

        $result = self::schema()->replace([
            "section"      => $section,
            "variable"     => $variable,
            "value"        => $value,
            "type"         => $model->type,
            "modifiedTime" => time(),
        ]);
        return $result > 0;
    }

    /**
     * Returns a Core Setting
     * @param Database $db
     * @param string   $variable
     * @return integer
     */
    public static function getCore(Database $db, string $variable): int {
        if (!$db->tableExists("settings")) {
            return 0;
        }
        $query  = Query::create("section", "=", self::Core);
        $query->add("variable", "=", $variable);

        $result = $db->getValue("settings", "value", $query);
        return !empty($result) ? $result : 0;
    }

    /**
     * Sets a Core Preference
     * @param Database $db
     * @param string   $variable
     * @param integer  $value
     * @return boolean
     */
    public static function setCore(Database $db, string $variable, int $value): bool {
        if (!$db->tableExists("settings")) {
            return false;
        }
        $result = $db->insert("settings", [
            "section"      => self::Core,
            "variable"     => $variable,
            "value"        => $value,
            "type"         => VariableType::Integer,
            "modifiedTime" => time(),
        ], "REPLACE");
        return $result > 0;
    }



    /**
     * Returns all the Settings
     * @param string|null $section  Optional.
     * @param boolean     $asObject Optional.
     * @return array{}|object
     */
    public static function getAll(?string $section = null, bool $asObject = false): array|object {
        $query   = Query::createIf("section", "=", $section);
        $request = self::schema()->getAll($query);
        $result  = [];

        foreach ($request as $row) {
            if (empty($result[$row["section"]])) {
                $result[$row["section"]] = [];
            }

            $value = $row["value"];
            $result[$row["section"]][$row["variable"]] = $value;
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
        $request = self::schema()->getAll();
        $batch   = [];

        foreach ($request as $row) {
            $variable = "{$row["section"]}-{$row["variable"]}";
            if (!isset($data[$variable])) {
                continue;
            }

            $batch[] = [
                "section"      => $row["section"],
                "variable"     => $row["variable"],
                "value"        => VariableType::encodeValue($row["type"], $data[$variable]),
                "type"         => $row["type"],
                "modifiedTime" => time(),
            ];
        }

        if (empty($batch)) {
            return false;
        }
        return self::schema()->batch($batch);
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
        $db = Framework::getDatabase();
        if (!$db->hasTable("settings")) {
            return false;
        }

        $settings  = Framework::loadData(Framework::SettingsData);
        $query     = Query::create("section", "<>", self::Core);
        $request   = $db->getAll("settings", "*", $query);

        $variables = [];
        $adds      = [];
        $renames   = [];
        $modifies  = [];
        $deletes   = [];

        // Add/Update Settings
        foreach ($settings as $section => $data) {
            foreach ($data as $variable => $value) {
                $found = false;
                $type  = VariableType::get($value);

                foreach ($request as $row) {
                    if ($row["section"] === $section && $row["variable"] === $variable) {
                        if ($row["type"] !== $type) {
                            $modifies[] = [
                                "section"  => $row["section"],
                                "variable" => $row["variable"],
                                "type"     => $type,
                            ];
                        }
                        $found = true;
                        break;
                    }

                    if (Strings::isEqual($row["section"], $section) && Strings::isEqual($row["variable"], $variable)) {
                        $renames[] = [
                            "section"  => $row["section"],
                            "variable" => $row["variable"],
                            "fields"   => [
                                "section"  => $section,
                                "variable" => $variable,
                                "type"     => $type,
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
                        "value"        => VariableType::encodeValue($type, $value),
                        "type"         => $type,
                        "modifiedTime" => time(),
                    ];
                    $adds[]      = $fields;
                    $request[]   = $fields;
                }
            }
        }

        // Remove Settings
        foreach ($request as $row) {
            $found = false;
            foreach ($settings as $section => $data) {
                foreach ($data as $variable => $value) {
                    if (Strings::isEqual($row["section"], $section) && Strings::isEqual($row["variable"], $variable)) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                $deletes[] = [ $row["section"], $row["variable"] ];
            }
        }

        // Process the SQL
        $didUpdate = false;
        if (!empty($adds)) {
            print("<br>Added <i>" . count($adds) . " settings</i><br>");
            print(Strings::join($variables, ", ") . "<br>");
            $db->batch("settings", $adds);
            $didUpdate = true;
        }

        if (!empty($renames)) {
            print("<br>Renamed <i>" . count($renames) . " settings</i><br>");
            foreach ($renames as $row) {
                $query = Query::create("section", "=", $row["section"]);
                $query->add("variable", "=", $row["variable"]);
                $db->update("settings", $row["fields"], $query);
            }
            $didUpdate = true;
        }

        if (!empty($modifies)) {
            print("<br>Modified <i>" . count($modifies) . " settings</i><br>");
            foreach ($modifies as $row) {
                $query = Query::create("section", "=", $row["section"]);
                $query->add("variable", "=", $row["variable"]);
                $db->update("settings", [ "type" => $row["type"] ], $query);
            }
            $didUpdate = true;
        }

        if (!empty($deletes)) {
            print("<br>Deleted <i>" . count($deletes) . " settings</i><br>");
            $variables = [];
            foreach ($deletes as $row) {
                $query = Query::create("section", "=", $row[0]);
                $query->add("variable", "=", $row[1]);
                $db->delete("settings", $query);
                $variables[] = $row[0] . "_" . $row[1];
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



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $data = Framework::loadData(Framework::SettingsData);
        if (empty($data)) {
            return [];
        }

        [ $variables, $hasJSON ] = self::getVariables($data);
        return [
            "sections"  => self::getSections($data),
            "variables" => $variables,
            "hasJSON"   => $hasJSON,
        ];
    }

    /**
     * Returns the Settings Sections for the generator
     * @param array{} $data
     * @return mixed[]
     */
    private static function getSections(array $data): array {
        $result  = [];

        foreach (array_keys($data) as $section) {
            if (!Strings::isEqual($section, self::General)) {
                $result[] = [
                    "section" => $section,
                    "name"    => Strings::upperCaseFirst($section),
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the Settings Variables for the generator
     * @param array{} $data
     * @return mixed[]
     */
    private static function getVariables(array $data): array {
        $result  = [];
        $isFirst = true;
        $hasJSON = false;

        foreach ($data as $section => $variables) {
            foreach ($variables as $variable => $value) {
                $isGeneral = Strings::isEqual($section, self::General);
                $prefix    = !$isGeneral ? Strings::upperCaseFirst($section) : "";
                $title     = Strings::camelCaseToTitle($variable);
                $type      = VariableType::get($value);

                $result[]  = [
                    "isFirst"   => $isFirst,
                    "section"   => $section,
                    "variable"  => $variable,
                    "prefix"    => $prefix,
                    "title"     => !empty($prefix) ? "$prefix $title" : $title,
                    "name"      => Strings::upperCaseFirst($variable),
                    "type"      => VariableType::getType($type),
                    "docType"   => VariableType::getDocType($type),
                    "getter"    => $type === VariableType::Boolean ? "is" : "get",
                    "isBoolean" => $type === VariableType::Boolean,
                    "isInteger" => $type === VariableType::Integer,
                    "isFloat"   => $type === VariableType::Float,
                    "isString"  => $type === VariableType::String,
                    "isArray"   => $type === VariableType::Array,
                ];

                $isFirst = false;
                if ($type === VariableType::Array) {
                    $hasJSON = true;
                }
            }
        }
        return [ $result, $hasJSON ];
    }
}
