<?php
namespace Framework\Config;

use Framework\Framework;
use Framework\Config\SettingType;
use Framework\Schema\Factory;
use Framework\Schema\Database;
use Framework\Schema\Query;
use Framework\Utils\JSON;

/**
 * The Settings Data
 */
class Settings {
    
    private static $loaded = false;
    private static $schema = null;
    
    
    /**
     * Loads the Settings Schemas
     * @return void
     */
    public static function getSchema() {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$schema = Factory::getSchema("settings");
        }
        return self::$schema;
    }
    
    

    /**
     * Returns a single Setting
     * @param string $variable
     * @param string $section  Optional.
     * @return mixed|null
     */
    public static function get($variable, $section = "general") {
        $query = Query::create("section", "=", $section);
        $query->add("variable", "=", $variable);
        $model = self::getSchema()->getOne($query);
        if (!$model->isEmpty()) {
            return SettingType::parseValue($model);
        }
        return null;
    }

    /**
     * Returns the Settings merging both Schemas
     * @param string $section Optional.
     * @return array
     */
    private static function getSettings($section = null) {
        $query = Query::createIf("section", "=", $section);
        return self::getSchema()->getArray($query);
    }

    /**
     * Returns all the Settings
     * @param string $section Optional.
     * @return array
     */
    public function getAll($section = null) {
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
        return $result;
    }

    /**
     * Returns all the Settings Parsed
     * @param string $section Optional.
     * @return array
     */
    public function getAllParsed($section = null) {
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
     * Saves the given Settings if those are already on the DB
     * @param array $data
     * @return void
     */
    public static function save(array $data) {
        $request = self::getSettings();
        $batch   = [];
        
        foreach ($request as $row) {
            $variable = $row["section"] . "-" . $row["variable"];
            if (isset($data[$variable])) {
                $value = $data[$variable];
                if ($row["type"] == SettingType::JSON) {
                    $value = JSON::fromCSV($value);
                }
                $batch[] = [
                    "section"      => $row["section"],
                    "variable"     => $row["variable"],
                    "value"        => $value,
                    "type"         => $row["type"],
                    "modifiedTime" => time(),
                ];
            }
        }
        
        if (!empty($batch)) {
            self::$schema->batch($batch);
        }
    }



    /**
     * Migrates the Settings
     * @param Database $db
     * @return void
     */
    public static function migrate(Database $db) {
        $request   = $db->getAll("settings");
        $settings  = Framework::loadData(Framework::SettingsData);

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
                        "section"  => $section,
                        "variable" => $variable,
                        "value"    => $type == SettingType::JSON ? JSON::encode($value) : $value,
                        "type"     => $type,
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
            print(implode($variables, ", ") . "<br>");
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
            print(implode($variables, ", ") . "<br>");
        }
        if (empty($adds) && empty($deletes)) {
            print("<br>No <i>settings</i> added or deleted <br>");
        }
    }
}
