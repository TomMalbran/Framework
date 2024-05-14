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

    const Active   = "Active";
    const Inactive = "Inactive";



    /**
     * Generates the Class
     * @return boolean
     */
    public static function generate(): bool {
        $data    = Framework::loadJSON(Framework::DataDir, Framework::StatusData, true);
        $appData = Framework::loadData(Framework::StatusData);
        if (!empty($appData)) {
            $data = array_merge($data, $appData);
        }

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "Status.mu");

        $contents  = Mustache::render($template, [
            "namespace" => Framework::Namespace,
            "statuses"  => self::getStatues($data),
            "groups"    => self::getGroups($data),
        ]);
        File::create($writePath, "Status.php", $contents);

        print("Generated the <i>Status</i><br>");
        return true;
    }

    /**
     * Generates the Statues data
     * @param array{} $data
     * @return array{}[]
     */
    private static function getStatues(array $data): array {
        $result    = [];
        $used      = [];
        $maxLength = 0;
        $lastGroup = "";

        foreach ($data as $group => $values) {
            foreach ($values as $name) {
                if (!empty($used[$name])) {
                    continue;
                }

                $maxLength = max($maxLength, Strings::length($name));
                $addSpace  = $group !== $lastGroup;
                if ($addSpace) {
                    $lastGroup = $group;
                }

                $result[] = [
                    "addSpace" => $addSpace,
                    "group"    => $group,
                    "statuses" => Strings::join($values, ", "),
                    "name"     => $name,
                ];
                $used[$name] = true;
            }
        }
        foreach ($result as $index => $status) {
            $result[$index]["constant"] = Strings::padRight($status["name"], $maxLength);
        }

        return $result;
    }

    /**
     * Generates the Groups data
     * @param array{} $data
     * @return array{}[]
     */
    private static function getGroups(array $data): array {
        $result = [];
        foreach ($data as $name => $values) {
            $result[] = [
                "name"     => $name,
                "values"   => "self::" . Strings::join($values, ", self::"),
                "statuses" => Strings::join($values, ", "),

            ];
        }

        return $result;
    }
}
