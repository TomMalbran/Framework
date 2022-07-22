<?php
namespace Framework\Config;

use Framework\Framework;
use Framework\Config\SettingType;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Database;
use Framework\Schema\Query;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Settings Data
 */
class Settings {

    /**
     * Loads the Settings Schemas
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("settings");
    }



    /**
     * Returns a single Setting
     * @param string $preference
     * @return mixed|null
     */
    public static function get(string $preference) {
        $section  = "general";
        $variable = $preference;

        if (Strings::contains($preference, "-")) {
            [ $section, $variable ] = Strings::split($preference, "-");
        }

        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);
        $model = self::schema()->getOne($query);

        if (!$model->isEmpty()) {
            return SettingType::parseValue($model);
        }
        return null;
    }

    /**
     * Returns a single Setting as an Integer
     * @param string $preference
     * @return integer
     */
    public static function getInt(string $preference): int {
        $result = self::get($preference);
        if ($result !== null) {
            return (int)$result;
        }
        return 0;
    }



    /**
     * Returns the Settings
     * @param string $section Optional.
     * @return array
     */
    private static function getSettings(string $section = null): array {
        $query = Query::createIf("section", "=", $section);
        return self::schema()->getAll($query);
    }

    /**
     * Returns all the Settings
     * @param string  $section  Optional.
     * @param boolean $asObject Optional.
     * @return array|object
     */
    public static function getAll(string $section = null, bool $asObject = false) {
        $request = self::getSettings($section);
        $result  = [];

        foreach ($request as $row) {
            if (empty($result[$row["section"]])) {
                $result[$row["section"]] = [];
            }

            $value = $row["value"];
            if ($row["type"] == SettingType::JSON) {
                $value = JSON::toCSV($row["value"]);
            }
            $result[$row["section"]][$row["variable"]] = $value;
        }

        if (!empty($section)) {
            $result = !empty($result[$section]) ? $result[$section] : [];
        }
        return $asObject ? (object)$result : $result;
    }

    /**
     * Returns all the Settings Parsed
     * @param string $section Optional.
     * @return array
     */
    public static function getAllParsed(string $section = null): array {
        $request = self::getSettings($section);
        $result  = [];

        foreach ($request as $row) {
            if (empty($result[$row["section"]])) {
                $result[$row["section"]] = [];
            }
            $value = SettingType::parseValue($row);
            $result[$row["section"]][$row["variable"]] = $value;
        }
        return $result;
    }



    /**
     * Sets a Core Preference
     * @param string  $section
     * @param string  $variable
     * @param integer $value
     * @return void
     */
    public static function set(string $section, string $variable, int $value): void {
        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);
        $model = self::schema()->getOne($query);
        if ($model->isEmpty()) {
            return;
        }

        self::schema()->replace([
            "section"      => $section,
            "variable"     => $variable,
            "value"        => SettingType::encodeValue($model->type, $value),
            "type"         => $model->type,
            "modifiedTime" => time(),
        ]);
    }

    /**
     * Saves the given Settings if those are already on the DB
     * @param array $data
     * @return void
     */
    public static function save(array $data): void {
        $request = self::getSettings();
        $batch   = [];

        foreach ($request as $row) {
            $variable = "{$row["section"]}-{$row["variable"]}";
            if (!isset($data[$variable])) {
                continue;
            }

            $batch[] = [
                "section"      => $row["section"],
                "variable"     => $row["variable"],
                "value"        => SettingType::encodeValue($row["type"], $data[$variable]),
                "type"         => $row["type"],
                "modifiedTime" => time(),
            ];
        }

        if (!empty($batch)) {
            self::schema()->batch($batch);
        }
    }

    /**
     * Saves the given Settings from the given section
     * @param string $section
     * @param array  $data
     * @return void
     */
    public static function saveIntoSection(string $section, array $data): void {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields["$section-$key"] = $value;
        }
        self::save($fields);
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
        $query  = Query::create("section", "=", "core")->add("variable", "=", $variable);
        $result = $db->getValue("settings", "value", $query);
        return !empty($result) ? $result : 0;
    }

    /**
     * Sets a Core Preference
     * @param Database $db
     * @param string   $variable
     * @param integer  $value
     * @return void
     */
    public static function setCore(Database $db, string $variable, int $value): void {
        if ($db->tableExists("settings")) {
            $db->insert("settings", [
                "section"      => "core",
                "variable"     => $variable,
                "value"        => $value,
                "type"         => SettingType::General,
                "modifiedTime" => time(),
            ], "REPLACE");
        }
    }

    /**
     * Returns a Core Data Setting
     * @param Database $db
     * @param string   $variable
     * @return array
     */
    public static function getCoreData(Database $db, string $variable) {
        $query  = Query::create("section", "=", "general")->add("variable", "=", $variable);
        $result = $db->getValue("settings", "value", $query);
        return !empty($result) ? JSON::decode($result, true) : null;
    }

    /**
     * Sets a Core Data Preference
     * @param Database $db
     * @param string   $variable
     * @param mixed    $value
     * @return void
     */
    public static function setCoreData(Database $db, string $variable, $value): void {
        $db->insert("settings", [
            "section"      => "general",
            "variable"     => $variable,
            "value"        => JSON::encode($value),
            "type"         => SettingType::JSON,
            "modifiedTime" => time(),
        ], "REPLACE");
    }



    /**
     * Migrates the Settings
     * @param Database $db
     * @return void
     */
    public static function migrate(Database $db): void {
        if (!$db->hasTable("settings")) {
            return;
        }
        $settings  = Framework::loadData(Framework::SettingsData);
        $request   = $db->getAll("settings");

        $variables = [];
        $adds      = [];
        $deletes   = [];

        // Adds Settings
        foreach ($settings as $section => $data) {
            foreach ($data as $variable => $value) {
                $found = false;
                foreach ($request as $row) {
                    if ($row["section"] == $section && $row["variable"] == $variable) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $type        = SettingType::get($value);
                    $variables[] = "{$section}_{$variable}";
                    $fields      = [
                        "section"      => $section,
                        "variable"     => $variable,
                        "value"        => SettingType::encodeValue($type, $value),
                        "type"         => $type,
                        "modifiedTime" => time(),
                    ];
                    $adds[]      = $fields;
                    $request[]   = $fields;
                }
            }
        }

        // Removes Settings
        foreach ($request as $row) {
            $found = false;
            foreach ($settings as $section => $data) {
                foreach ($data as $variable => $value) {
                    if ($row["section"] == $section && $row["variable"] == $variable) {
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
        if (!empty($adds)) {
            print("<br>Added <i>" . count($adds) . " settings</i><br>");
            print(Strings::join($variables, ", ") . "<br>");
            $db->batch("settings", $adds);
        }
        if (!empty($deletes)) {
            print("<br>Deleted <i>" . count($deletes) . " settings</i><br>");
            $variables = [];
            foreach ($deletes as $row) {
                $query = Query::create("section", "=", $row[0])->add("variable", "=", $row[1]);
                $db->delete("settings", $query);
                $variables[] = $row[0] . "_" . $row[1];
            }
            print(Strings::join($variables, ", ") . "<br>");
        }
        if (empty($adds) && empty($deletes)) {
            print("<br>No <i>settings</i> added or deleted<br>");
        }
    }
}
