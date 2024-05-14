<?php
namespace Framework\System;

use Framework\Framework;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Statuses
 */
class Statuses {

    const Active   = 1;
    const Inactive = 2;


    private static bool  $loaded = false;

    /** @var array{} */
    private static array $data = [];



    /**
     * Loads the Status Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$data = Framework::loadData(Framework::StatusData);
        if (empty(self::$data)) {
            self::$data = Framework::loadJSON(Framework::DataDir, Framework::StatusData, true);
        }
        self::$loaded = true;
        return true;
    }

    /**
     * Returns true if the given Status Value is valid for the given Group
     * @param integer $value
     * @return boolean
     */
    public static function isValid(int $value): bool {
        return Arrays::contains([ self::Active, self::Inactive ], $value);
    }



    /**
     * Generates the Class
     * @return boolean
     */
    public static function migrate(): bool {
        self::load();

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "Status.mu");

        $contents  = Mustache::render($template, [
            "namespace" => Framework::Namespace,
            "statuses"  => self::getStatues(),
            "groups"    => self::getGroups(),
        ]);
        File::create($writePath, "Status.php", $contents, true);

        print("<br>Generated the <i>Status</i><br>");
        return true;
    }

    /**
     * Generates the Statues data
     * @return array{}[]
     */
    private static function getStatues(): array {
        $result    = [];
        $maxLength = 0;
        $nextGroup = 0;

        foreach (self::$data["values"] as $name => $value) {
            $maxLength = max($maxLength, Strings::length($name));
            $addSpace  = $value > $nextGroup + 10;
            if ($addSpace) {
                $nextGroup = floor($value / 10) * 10;
            }

            $result[] = [
                "name"     => $name,
                "value"    => $value,
                "addSpace" => $addSpace,
            ];
        }
        foreach ($result as $index => $status) {
            $result[$index]["constant"] = Strings::padRight($status["name"], $maxLength);
        }

        return $result;
    }

    /**
     * Generates the Groups data
     * @return array{}[]
     */
    private static function getGroups(): array {
        $result = [];
        foreach (self::$data["groups"] as $name => $values) {
            $statues = [];
            foreach ($values as $value) {
                if (isset(self::$data["values"][$value])) {
                    $statues[] = self::$data["values"][$value];
                }
            }
            if (empty($statues)) {
                continue;
            }

            $result[] = [
                "name"   => $name,
                "values" => Strings::join($statues, ", "),
            ];
        }

        return $result;
    }
}
