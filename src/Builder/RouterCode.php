<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Discovery\Route;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use ReflectionNamedType;

/**
 * The Router Code
 */
class RouterCode {

    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        $reflections = Discovery::getReflectionClasses(skipIgnored: true);
        $routes      = [];
        $usedRoutes  = [];
        $errorRoutes = [];
        $testRoutes  = [];

        foreach ($reflections as $className => $reflection) {
            $baseRoute   = "";
            $testMethods = [];

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

                // Add the Test Route
                $testMethods[$method->getName()] = $route->access->name;
                if ($baseRoute === "") {
                    $baseRoute = Strings::substringBefore($route->route, "/", false);
                }
            }

            // Store the Test Routes
            if (count($testMethods) > 0) {
                $testRoutes[$baseRoute] = [
                    "static" => true,
                    "module" => Strings::substringAfter($className, "App\\"),
                    "routes" => $testMethods,
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

        // Save the Test Data
        $oldData = Discovery::loadData(DataFile::Route);
        if (!Arrays::isEmpty($oldData) && !Arrays::isEmpty($testRoutes)) {
            Discovery::saveData("routesTest", $testRoutes);
        }

        // Show the Error Routes
        if (count($errorRoutes) > 0) {
            print("\nROUTES WITH ERROR:\n");
            foreach ($errorRoutes as $errorRoute) {
                print("- $errorRoute\n\n");
            }
            print("\n");
        }

        return [
            "hasRoutes" => count($routes) > 0,
            "routes"    => $routes,
        ];
    }
}
