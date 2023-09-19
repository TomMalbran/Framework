<?php
namespace Framework\Route;

use Framework\Framework;
use Framework\Request;
use Framework\Route\Container;
use Framework\Auth\Access;
use Framework\Response;
use Framework\Utils\Strings;

/**
 * The Router Service
 */
class Router {

    const Namespace  = "App\\";
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
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::RouteData);
        return true;
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
     * @param string  $route
     * @param Request $request
     * @return mixed
     */
    public static function call(string $route, Request $request): mixed {
        $data = self::get($route);
        if ($data->access == null) {
            return null;
        }
        return self::execute($data->static, $data->module, $data->method, $request);
    }

    /**
     * Executes the given Method in the given Module
     * @param boolean $isStatic
     * @param string  $module
     * @param string  $method
     * @param mixed   ...$params
     * @return mixed
     */
    public static function execute(bool $isStatic, string $module, string $method, mixed ...$params): mixed {
        if ($isStatic) {
            return call_user_func_array(self::Namespace . $module . "::" . $method, $params);
        }

        $instance = Container::bind(self::Controller . $module);
        return call_user_func_array([ $instance, $method ], $params);
    }
}
