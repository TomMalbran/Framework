<?php
namespace Framework\Builder;

use Framework\Framework;
use Framework\Core\Listener;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Throwable;

/**
 * The Signal Code
 */
class SignalCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $classes = Framework::findClasses(skipIgnored: true);
        $signals = [];
        $uses    = [];

        foreach ($classes as $className) {
            try {
                $reflection = new ReflectionClass($className);
            } catch (Throwable $e) {
                continue;
            }

            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Listener::class);
                if (empty($attributes)) {
                    continue;
                }

                $attribute = $attributes[0];
                $listener  = $attribute->newInstance();

                foreach ($listener->triggers as $trigger) {
                    $params = self::getParams($method, $uses);

                    if (empty($signals[$trigger])) {
                        $signals[$trigger] = [
                            "event"    => $trigger,
                            "params"   => $params,
                            "triggers" => [],
                        ];
                    } elseif (count($params) > count($signals[$trigger]["params"])) {
                        $signals[$trigger]["params"] = $params;
                    }

                    $triggerClass = "{$className}::{$method->getName()}";
                    $signals[$trigger]["triggers"][] = [
                        "name"   => $triggerClass,
                        "params" => $params,
                    ];
                }
            }
        }

        $signals = array_values($signals);
        $signals = Arrays::sort($signals, function ($a, $b) {
            return $a["event"] <=> $b["event"];
        });

        if (empty($signals)) {
            return [];
        }
        return [
            "uses"    => array_keys($uses),
            "hasUses" => !empty($uses),
            "signals" => $signals,
        ];
    }

    /**
     * Generates the Params of the Method
     * @param ReflectionMethod $method
     * @param array{}          $uses
     * @return array{}[]
     */
    private static function getParams(ReflectionMethod $method, array &$uses): array {
        $parameters = $method->getParameters();
        $params     = [];
        $typeLength = 0;

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();
            $types     = [];

            if ($paramType instanceof ReflectionUnionType) {
                $types = $paramType->getTypes();
            } else {
                $types = [ $paramType ];
            }

            $typeNames = [];
            $docTypes  = [];
            foreach ($types as $type) {
                $name = $type ? $type->getName() : "mixed";
                if (!$type->isBuiltin()) {
                    $uses[$name] = 1;
                    $name = Strings::substringAfter($name, "\\");
                }

                $docType = match ($name) {
                    "int"   => "integer",
                    "bool"  => "boolean",
                    default => $name,
                };
                if (Strings::endsWith($paramName, "IDs")) {
                    $docType = "integer[]";
                }

                $typeNames[] = $name;
                $docTypes[]  = $docType;
            }

            $typeName   = Strings::join($typeNames, "|");
            $docType    = Strings::join($docTypes, "|");
            $typeLength = max($typeLength, Strings::length($docType));

            $params[] = [
                "isFirst" => false,
                "name"    => $parameter->getName(),
                "type"    => $typeName,
                "docType" => $docType,
            ];
        }

        foreach ($params as $index => $param) {
            $params[$index]["docType"] = Strings::padRight($param["docType"], $typeLength);
        }
        $params[0]["isFirst"] = true;

        return $params;
    }
}
