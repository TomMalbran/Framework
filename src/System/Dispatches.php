<?php
namespace Framework\System;

use Framework\Framework;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Dispatches Generator
 */
class Dispatches {

    /** @var array{}[] */
    private static array $data   = [];
    private static bool  $loaded = false;


    /**
     * Loads the Dispatcher Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::DispatchData);
        return true;
    }



    /**
     * Generates the Class
     * @return boolean
     */
    public static function migrate(): bool {
        self::load();

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "Dispatcher.mu");

        $uses      = self::getUses();
        $contents  = Mustache::render($template, [
            "namespace"   => Framework::Namespace,
            "uses"        => $uses,
            "hasUses"     => !empty($uses),
            "dispatchers" => self::getDispatchers(),
        ]);
        File::create($writePath, "Dispatcher.php", $contents, true);

        print("<br>Generated the <i>Dispatcher</i><br>");
        return true;
    }

    /**
     * Generates the Dispatchers data
     * @return array{}[]
     */
    private static function getDispatchers(): array {
        $result = [];
        foreach (self::$data as $event => $data) {
            if (empty($data["params"])) {
                continue;
            }

            $params     = [];
            $typeLength = 0;
            foreach ($data["params"] as $name => $type) {
                $realType = match ($type) {
                    "int[]" => "array",
                    default => $type,
                };
                $docType = match ($type) {
                    "int"   => "integer",
                    "int[]" => "integer[]",
                    "bool"  => "boolean",
                    default => $type,
                };
                $typeLength = max($typeLength, Strings::length($docType));

                $params[] = [
                    "isFirst" => false,
                    "name"    => $name,
                    "type"    => $realType,
                    "docType" => $docType,
                ];
            }
            foreach ($params as $index => $param) {
                $params[$index]["docType"] = Strings::padRight($param["docType"], $typeLength);
            }
            $params[0]["isFirst"] = true;

            $result[] = [
                "event"      => $event,
                "params"     => $params,
                "dispatches" => $data["dispatches"],
            ];
        }
        return $result;
    }

    /**
     * Generates the Uses data
     * @return string[]
     */
    private static function getUses(): array {
        $result = [];
        foreach (self::$data as $data) {
            if (!empty($data["params"])) {
                foreach ($data["params"] as $type) {
                    if (Strings::endsWith($type, "Entity") && !Arrays::contains($result, $type)) {
                        $result[] = $type;
                    }
                }
            }
        }
        return $result;
    }
}
