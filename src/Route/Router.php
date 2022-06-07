<?php
namespace Framework\Route;

use Framework\Framework;
use Framework\Request;
use Framework\Route\Container;
use Framework\Auth\Access;
use Framework\Utils\Strings;

/**
 * The Router Service
 */
class Router {

    const Namespace  = "App\\";
    const Controller = "App\\Controller\\";

    private static $loaded = false;
    private static $data   = [];


    /**
     * Loads the Routes Data
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$data   = Framework::loadData(Framework::RouteData);
        }
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
        $data = self::get($route);
        return $data->access != null;
    }

    /**
     * Returns the Access Level for the given Route, if it exists
     * @param string $route
     * @return integer
     */
    public static function getAccess(string $route): int {
        $data = self::get($route);
        return Access::getOne($data->access);
    }



    /**
     * Calls the given Route with the given params, if it exists
     * @param string $route
     * @param array  $params Optional.
     * @return Response|null
     */
    public static function call(string $route, array $params = null) {
        $data = self::get($route);
        if ($data->access == null) {
            return null;
        }

        $request = new Request($params);
        if ($data->static) {
            return call_user_func_array(self::Namespace . "{$data->module}::{$data->method}", [ $request ]);
        }

        $instance = Container::create(self::Controller . $data->module);
        return call_user_func_array([ $instance, $data->method ], [ $request ]);
    }
}
