<?php
namespace Framework;

use Framework\Framework;
use Framework\Container;
use Framework\Request;

/**
 * The Router Service
 */
class Router {

    private static $loaded  = false;
    private static $baseUse = "App\\Controller\\";
    private static $data    = [];
    
    
    /**
     * Loads the Routes Data
     * @return void
     */
    public static function load() {
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
    public function get($route) {
        self::load();
        $base   = substr($route, 0, strripos($route, "/"));
        $method = str_replace($base, "/", $route);

        $data   = isset(self::$data[$base]) ? self::$data[$base] : null;
        $module = $data != null ? $data->module : null;
        $access = $data != null ? $data->routes[$method] : null;

        return (object)[
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
    public static function has($route) {
        $data = self::get($route);
        return $data->access != null;
    }



    /**
     * Returns the Access Level for the given Route, if it exists
     * @param string $route
     * @return string|null
     */
    public static function getAccess($route) {
        $data = self::get($route);
        return $data->access;
    }
    
    /**
     * Returns the Method for the given Route, if it exists
     * @param string $route
     * @return string
     */
    public static function getMethod($route) {
        $data = self::get($route);
        return $data->method;
    }

    /**
     * Returns the Instance for the given Route, if it exists
     * @param string $route
     * @return object|null
     */
    public static function getInstance($route) {
        $data = self::get($route);
        if ($data->access != null) {
            return Container::bind(self::$baseUse . $data->module);
        }
        return null;
    }



    /**
     * Calls the given Route with the given params, if it exists
     * @param string $route
     * @param array  $params Optional.
     * @return object|null
     */
    public function call($route, array $params = null) {
        $data = self::get($route);
        if ($data->access != null) {
            $instance = Container::bind(self::$baseUse . $data->module);
            $request  = new Request($params);
            return call_user_func_array([ $instance, $data->method ], [ $request ]);
        }
        return null;
    }
}