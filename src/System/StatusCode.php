<?php
namespace Framework\System;

use Framework\Framework;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Strings;

/**
 * The Status Code
 */
class StatusCode {

    const Active   = 1;
    const Inactive = 2;



    /**
     * Generates the Class
     * @return boolean
     */
    public static function generate(): bool {
        $data = Framework::loadData(Framework::StatusData, false);
        if (empty($data)) {
            $data = Framework::loadJSON(Framework::DataDir, Framework::StatusData, true, false);
        }
        if (empty($data) || empty($data->values)) {
            return false;
        }

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "Status.mu");

        $contents  = Mustache::render($template, [
            "namespace" => Framework::Namespace,
            "statuses"  => self::getStatues($data),
            "groups"    => self::getGroups($data),
        ]);
        File::create($writePath, "Status.php", $contents);

        print("<br>Generated the <i>Status</i><br>");
        return true;
    }

    /**
     * Generates the Statues data
     * @param object $data
     * @return array{}[]
     */
    private static function getStatues(object $data): array {
        $result    = [];
        $maxLength = 0;
        $nextGroup = 0;

        foreach ($data->values as $name => $value) {
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
     * @param object $data
     * @return array{}[]
     */
    private static function getGroups(object $data): array {
        $result = [];
        foreach ($data->groups as $name => $values) {
            $statues = [];
            foreach ($values as $value) {
                if (isset($data->values->$value)) {
                    $statues[] = $data->values->$value;
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
