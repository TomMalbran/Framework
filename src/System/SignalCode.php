<?php
namespace Framework\System;

use Framework\Framework;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Signal Code
 */
class SignalCode {

    /**
     * Generates the Class
     * @return boolean
     */
    public static function generate(): bool {
        $data = Framework::loadData(Framework::SignalData, false);
        if (empty((array)$data)) {
            return false;
        }

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "Signal.mu");

        $uses      = self::getUses($data);
        $contents  = Mustache::render($template, [
            "namespace" => Framework::Namespace,
            "uses"      => $uses,
            "hasUses"   => !empty($uses),
            "signals"   => self::getSignals($data),
        ]);
        File::create($writePath, "Signal.php", $contents);

        print("Generated the <i>Signal</i><br>");
        return true;
    }

    /**
     * Generates the Signals data
     * @param object $data
     * @return array{}[]
     */
    private static function getSignals(object $data): array {
        $result = [];
        foreach ($data as $event => $signal) {
            if (empty($signal->params)) {
                continue;
            }

            $params     = [];
            $typeLength = 0;
            foreach ($signal->params as $name => $type) {
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
                "event"    => $event,
                "params"   => $params,
                "triggers" => $signal->triggers,
            ];
        }
        return $result;
    }

    /**
     * Generates the Uses data
     * @param object $data
     * @return string[]
     */
    private static function getUses(object $data): array {
        $result = [];
        foreach ($data as $signal) {
            if (!empty($signal->params)) {
                foreach ($signal->params as $type) {
                    if (Strings::endsWith($type, "Entity") && !Arrays::contains($result, $type)) {
                        $result[] = $type;
                    }
                }
            }
        }
        return $result;
    }
}
