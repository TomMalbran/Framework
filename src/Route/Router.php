<?php
namespace Framework\Route;

use Framework\Request;

/**
 * The Router
 * NOTE: This class is generated automatically by the System Code.
 */
class Router {

    /**
     * Returns true if the give Route exists
     * @param string $route
     * @return boolean
     */
    public static function has(string $route): bool {
        return false;
    }

    /**
     * Returns the Access Name for the given Route, if it exists
     * @param string $route
     * @return string
     */
    public static function getAccessName(string $route): string {
        return "";
    }

    /**
     * Calls the given Route with the given params, if it exists
     * @param string  $route
     * @param Request $request
     * @return mixed
     */
    public static function call(string $route, Request $request): mixed {
        return [];
    }
}
