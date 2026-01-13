<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Discovery\Route;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

use ReflectionNamedType;

/**
 * The Router Code
 */
#[Priority(Priority::Lowest)]
class RouterCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
        $reflections = Discovery::getReflectionClasses();
        $routes      = [];
        $usedRoutes  = [];
        $errorRoutes = [];

        foreach ($reflections as $className => $reflection) {
            // Get the Methods
            $methods = $reflection->getMethods();

            // Add a space between the classes
            if (count($methods) > 0 && count($routes) > 0) {
                $routes[count($routes) - 1]["addSpace"] = true;
            }

            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Route::class);
                if (!isset($attributes[0])) {
                    continue;
                }

                $route      = $attributes[0]->newInstance();
                $fileName   = $reflection->getFileName();
                $params     = $method->getNumberOfParameters();
                $response   = $method->getReturnType();
                $methodName = $method->getName();
                $startLine  = $method->getStartLine();

                // Check the Response
                if ($response === null || !$response instanceof ReflectionNamedType) {
                    $errorRoutes[] = "Required Response for $methodName: $fileName:$startLine";
                    continue;
                }
                $responseName = $response->getName();

                // Check the Route
                if (isset($usedRoutes[$route->route])) {
                    $errorRoutes[] = "Route already used for $methodName: $fileName:$startLine";
                    continue;
                }
                if ($responseName !== "never" && $responseName !== "Framework\\Response") {
                    $errorRoutes[] = "Wrong Response for $methodName: $fileName:$startLine";
                    continue;
                }
                if (!Strings::endsWith($route->route, "/$methodName")) {
                    $errorRoutes[] = "Route must end with $methodName: $fileName:$startLine";
                    continue;
                }

                // Add the Route
                $routes[] = [
                    "className"  => $className,
                    "method"     => $methodName,
                    "hasRequest" => $params > 0,
                    "route"      => $route->route,
                    "access"     => $route->access->name,
                    "addSpace"   => false,
                ];
                $usedRoutes[$route->route] = true;
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

        // Show the Error Routes
        if (count($errorRoutes) > 0) {
            print("\nROUTES WITH ERROR:\n");
            foreach ($errorRoutes as $errorRoute) {
                print("- $errorRoute\n\n");
            }
            print("\n");
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
     * @return integer
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
