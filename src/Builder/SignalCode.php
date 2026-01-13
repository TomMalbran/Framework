<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Discovery\Listener;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * The Signal Code
 */
#[Priority(Priority::Lowest)]
class SignalCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
        $reflections = Discovery::getReflectionClasses();
        $signals     = [];
        $uses        = [];

        foreach ($reflections as $className => $reflection) {
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Listener::class);
                if (!isset($attributes[0])) {
                    continue;
                }

                $attribute = $attributes[0];
                $listener  = $attribute->newInstance();

                foreach ($listener->triggers as $trigger) {
                    $params = self::getParams($method, $uses);

                    if (!isset($signals[$trigger])) {
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
        $signals = Arrays::sort($signals, function (array $a, array $b) {
            return $a["event"] <=> $b["event"];
        });


        // Builds the code if required
        if (Arrays::isEmpty($signals)) {
            return Builder::generateCode("Signal");
        }
        return Builder::generateCode("Signal", [
            "uses"    => array_keys($uses),
            "hasUses" => count($uses) > 0,
            "signals" => $signals,
            "total"   => count($signals),
        ]);
    }

    /**
     * Destroys the Code
     * @return integer
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }



    /**
     * Generates the Params of the Method
     * @param ReflectionMethod      $method
     * @param array<string,integer> $uses
     * @return array{isFirst:boolean,name:string,type:string,docType:string}[]
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
                $name = "mixed";

                if ($type instanceof ReflectionNamedType) {
                    $name = $type->getName();
                    if (!$type->isBuiltin()) {
                        $uses[$name] = 1;
                        $name = Strings::substringAfter($name, "\\");
                    }
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
