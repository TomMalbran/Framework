<?php
namespace Framework\Builder;

use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\Discovery;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Type\DiscoveryClass;
use Framework\Discovery\Attr\Priority;
use Framework\Discovery\Attr\Listener;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * The Signal Code
 * @phpstan-type SignalParam array{
 *   isFirst: bool,
 *   name:    string,
 *   type:    string,
 *   docType: string,
 * }
 * @phpstan-type SignalTrigger array{
 *   name:   string,
 *   params: list<SignalParam>,
 * }
 * @phpstan-type SignalData array{
 *   event:    string,
 *   params:   list<SignalParam>,
 *   triggers: list<SignalTrigger>,
 * }
 * @phpstan-type SignalResult array{
 *   uses?:    list<string>,
 *   hasUses?: bool,
 *   signals?: list<SignalData>,
 *   total?:   int,
 * }
 */
#[Priority(Priority::Lowest)]
class SignalCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    #[NotTested("It generates a file")]
    public static function generateCode(): int {
        $data = self::collectSignals(Discovery::findClasses());
        return Builder::generateCode("Signal", $data);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }



    /**
     * Collects the Signals used to generate the code
     * @param list<DiscoveryClass> $classes
     * @return SignalResult
     */
    public static function collectSignals(array $classes): array {
        $signals = [];
        $uses    = [];

        foreach ($classes as $class) {
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Listener::class);
                if (!isset($attributes[0])) {
                    continue;
                }

                $listener = $attributes[0]->newInstance();
                foreach ($listener->triggers as $trigger) {
                    $params = self::getParams($method, $uses);

                    if (!isset($signals[$trigger])) {
                        $signals[$trigger] = [
                            "event"    => $trigger,
                            "params"   => [],
                            "triggers" => [],
                        ];
                    }

                    // Adds the Params
                    foreach ($params as $param) {
                        $hasParam = false;
                        foreach ($signals[$trigger]["params"] as $existingParam) {
                            if ($existingParam["name"] === $param["name"]) {
                                $hasParam = true;
                                break;
                            }
                        }
                        if (!$hasParam) {
                            if (count($signals[$trigger]["params"]) > 0) {
                                $param["isFirst"] = false;
                            }
                            $signals[$trigger]["params"][] = $param;
                        }
                    }

                    // Adds the Triggers
                    $triggerClass = "{$class->getFullyQualifiedName()}::{$method->getName()}";
                    $signals[$trigger]["triggers"][] = [
                        "name"   => $triggerClass,
                        "params" => $params,
                    ];
                }
            }
        }

        $signals = Arrays::getValues($signals);
        $signals = Arrays::sortList($signals, function (array $a, array $b) {
            return $a["event"] <=> $b["event"];
        });


        if (Arrays::isEmpty($signals)) {
            return [];
        }
        return [
            "uses"    => array_keys($uses),
            "hasUses" => count($uses) > 0,
            "signals" => $signals,
            "total"   => count($signals),
        ];
    }

    /**
     * Generates the Params of the Method
     * @param ReflectionMethod  $method
     * @param array<string,int> $uses
     * @return list<SignalParam>
     */
    private static function getParams(
        ReflectionMethod $method,
        array &$uses,
    ): array {
        $parameters = $method->getParameters();
        $params     = [];

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

                $docType = $name;
                if (Strings::endsWith($paramName, "IDs")) {
                    $docType = "list<int>";
                }

                $typeNames[] = $name;
                $docTypes[]  = $docType;
            }

            if ($parameter->allowsNull()) {
                $typeNames[] = "null";
                $docTypes[]  = "null";
            }

            $params[] = [
                "isFirst" => false,
                "name"    => $parameter->getName(),
                "type"    => Strings::join($typeNames, "|"),
                "docType" => Strings::join($docTypes, "|"),
            ];
        }

        if (isset($params[0])) {
            $params[0]["isFirst"] = true;
        }

        return $params;
    }
}
