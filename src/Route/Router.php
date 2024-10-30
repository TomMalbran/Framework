<?php
namespace Framework\Route;

use App\System\Router as SystemRouter;

use Framework\Framework;
use Framework\Request;
use Framework\Route\Container;
use Framework\Utils\Strings;

/**
 * The Router Service
 */
class Router {

    const Controller = "App\\Controller\\";

    /** @var array{}[] */
    private static array $data   = [];
    private static bool  $loaded = false;


    /**
     * Loads the Router Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$data   = Framework::loadData(Framework::RouteData);
        self::$loaded = true;
        return true;
    }

    /**
     * Returns true if the System Router has the route
     * @param string $route
     * @return boolean
     */
    public static function hasSystemRoute(string $route): bool {
        /** @disregard P1009 */
        return class_exists(SystemRouter::class) && SystemRouter::has($route);
    }



    /**
     * Parses the Route and returns its parts
     * @param string $route
     * @return object
     */
    public static function get(string $route): object {
        self::load();
        $method = Strings::substringAfter($route, "/");
        $base   = Strings::stripEnd($route, "/$method");

        $data   = isset(self::$data[$base]) ? self::$data[$base] : [];
        $static = !empty($data["static"]) ? $data["static"] : false;
        $module = !empty($data["module"]) ? $data["module"] : null;
        $access = !empty($data["routes"]) && !empty($data["routes"][$method]) ? $data["routes"][$method] : null;

        return (object)[
            "static" => $static,
            "module" => $module,
            "method" => $method,
            "access" => $access,
        ];
    }

    /**
     * Returns true if the give Route exists
     * @param string $route
     * @return boolean
     */
    public static function has(string $route): bool {
        if (self::hasSystemRoute($route)) {
            return true;
        }

        $data = self::get($route);
        return $data->access != null;
    }

    /**
     * Returns the Access Name for the given Route, if it exists
     * @param string $route
     * @return string
     */
    public static function getAccessName(string $route): string {
        if (self::hasSystemRoute($route)) {
            /** @disregard P1009 */
            return SystemRouter::getAccessName($route);
        }

        $data = self::get($route);
        return $data->access;
    }



    /**
     * Calls the given Route with the given params, if it exists
     * @param string  $route
     * @param Request $request
     * @return mixed
     */
    public static function call(string $route, Request $request): mixed {
        if (self::hasSystemRoute($route)) {
            /** @disregard P1009 */
            return SystemRouter::call($route, $request);
        }

        $data = self::get($route);
        if ($data->access == null) {
            return null;
        }

        if ($data->static) {
            $callable = Framework::Namespace . $data->module . "::" . $data->method;
            return call_user_func_array($callable, [ $request ]);
        }

        $instance = Container::bind(self::Controller . $data->module);
        return call_user_func_array([ $instance, $data->method ], [ $request ]);
    }
}
