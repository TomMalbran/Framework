<?php
namespace Framework\Builder;

use Framework\Framework;
use Framework\Core\Route;
use Framework\Utils\Strings;

use ReflectionClass;
use Throwable;

/**
 * The Router Code
 */
class RouterCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $classes     = Framework::findClasses(skipIgnored: true);
        $routes      = [];
        $usedRoutes  = [];
        $errorRoutes = [];
        $testRoutes  = [];

        foreach ($classes as $className) {
            try {
                $reflection = new ReflectionClass($className);
            } catch (Throwable $e) {
                continue;
            }

            $baseRoute   = "";
            $testMethods = [];

            // Get the Methods
            $methods = $reflection->getMethods();

            // Add a space between the classes
            if (!empty($methods) && !empty($routes)) {
                $routes[count($routes) - 1]["addSpace"] = true;
            }

            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Route::class);
                if (empty($attributes)) {
                    continue;
                }

                $attribute  = $attributes[0];
                $fileName   = $reflection->getFileName();
                $route      = $attribute->newInstance();
                $params     = $method->getNumberOfParameters();
                $response   = $method->getReturnType();
                $methodName = $method->getName();
                $startLine  = $method->getStartLine();

                // Check the Route
                if (isset($usedRoutes[$route->route])) {
                    $errorRoutes[] = "Route already used for $methodName: $fileName:$startLine";
                    continue;
                }
                if ($response->getName() !== "Framework\\Response") {
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
                if (empty($baseRoute)) {
                    $baseRoute = Strings::substringBefore($route->route, "/", false);
                }
            }

            // Store the Test Routes
            if (!empty($testMethods)) {
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
        $oldData = Framework::loadData(Framework::RouteData);
        if (!empty($oldData) && !empty($testRoutes)) {
            Framework::saveData("routesTest", $testRoutes);
        }

        // Show the Error Routes
        if (!empty($errorRoutes)) {
            print("\nROUTES WITH ERROR:\n");
            foreach ($errorRoutes as $errorRoute) {
                print("- $errorRoute\n\n");
            }
            print("\n");
        }

        return [
            "hasRoutes" => !empty($routes),
            "routes"    => $routes,
        ];
    }
}
