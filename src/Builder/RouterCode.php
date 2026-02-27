<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Discovery\Route;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

/**
 * The Router Code
 */
#[Priority(Priority::Lowest)]
class RouterCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $reflections = Discovery::getReflectionClasses();
        $routes      = [];

        foreach ($reflections as $className => $reflection) {
            // Get the Methods
            $methods = $reflection->getMethods();

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
                $params     = $method->getNumberOfParameters();
                $methodName = $method->getName();

                // Add the Route
                $routes[] = [
                    "className"  => $className,
                    "method"     => $methodName,
                    "hasRequest" => $params > 0,
                    "route"      => $route->route,
                    "access"     => $route->access->toString(),
                    "addSpace"   => false,
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


        // Builds the code
        $total = count($routes);
        return Builder::generateCode("Router", [
            "hasRoutes" => $total > 0,
            "routes"    => $routes,
            "total"     => $total,
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
