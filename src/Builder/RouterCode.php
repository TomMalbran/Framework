<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Type\DiscoveryClass;
use Framework\Discovery\Attr\Priority;
use Framework\Discovery\Attr\Route;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

use ReflectionNamedType;

/**
 * The Router Code
 * @phpstan-type RouteData array{
 *   className:    string,
 *   method:       string,
 *   requestParam: string,
 *   route:        string,
 *   access:       string,
 *   addSpace:     bool,
 * }
 * @phpstan-type RouterResult array{
 *   hasRoutes: bool,
 *   routes:    list<RouteData>,
 *   total:     int,
 * }
 */
#[Priority(Priority::Lowest)]
class RouterCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $data = self::collectRoutes(Discovery::findClasses());
        return Builder::generateCode("Router", $data);
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
     * Collects the Routes from the given Classes
     * @param list<DiscoveryClass> $classes
     * @return RouterResult
     */
    public static function collectRoutes(array $classes): array {
        $routes = [];

        foreach ($classes as $class) {
            $methods = $class->getMethods();

            // Add a space between the classes
            $total = count($routes);
            if (count($methods) > 0 && $total > 0 && isset($routes[$total - 1])) {
                $routes[$total - 1]["addSpace"] = true;
            }

            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Route::class);
                if (!isset($attributes[0])) {
                    continue;
                }

                /** @var Route */
                $route      = $attributes[0]->newInstance();
                $methodName = $method->getName();

                // Check the Parameters
                $params       = $method->getParameters();
                $requestParam = "";

                // Get the first Param type if exists
                if (count($params) > 1) {
                    continue;
                }
                if (count($params) === 1) {
                    $paramType = $params[0]->getType();
                    if ($paramType === null) {
                        continue;
                    }
                    if (!$paramType instanceof ReflectionNamedType) {
                        continue;
                    }

                    $typeName = $paramType->getName();
                    if (Strings::endsWith($typeName, "\\Request")) {
                        $requestParam = "\$request";
                    } elseif (Strings::endsWith($typeName, "Request")) {
                        $requestParam = "\\$typeName::fromRequest(\$request)";
                    }
                }


                // Add the Route
                $routes[] = [
                    "className"    => $class->getFullyQualifiedName(),
                    "method"       => $methodName,
                    "requestParam" => $requestParam,
                    "route"        => $route->route,
                    "access"       => $route->access->toString(),
                    "addSpace"     => false,
                ];
            }
        }

        // Align the Routes
        $length = 0;
        foreach ($routes as $route) {
            $length = max($length, Strings::length($route["route"]) + 2);
        }
        foreach ($routes as $index => $route) {
            $routes[$index]["route"] = Strings::padRight("\"{$route["route"]}\"", $length);
        }


        $total = count($routes);
        return [
            "hasRoutes" => $total > 0,
            "routes"    => $routes,
            "total"     => $total,
        ];
    }
}
