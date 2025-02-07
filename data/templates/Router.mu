<?php
namespace {{codeSpace}};

use Framework\Request;

/**
 * The Router
 */
class Router {

    /**
     * Returns true if the give Route exists
     * @param string $route
     * @return boolean
     */
    public static function has(string $route): bool {
        return self::getAccessName($route) !== "";
    }

    /**
     * Returns the Access Name for the given Route, if it exists
     * @param string $route
     * @return string
     */
    public static function getAccessName(string $route): string {
    {{#hasRoutes}}
        return match ($route) {
        {{#routes}}
            {{{route}}} => "{{access}}",
            {{#addSpace}}

            {{/addSpace}}
        {{/routes}}

            default => "",
        };
    {{/hasRoutes}}
    {{^hasRoutes}}
        return "";
    {{/hasRoutes}}
    }

    /**
     * Calls the given Route with the given params, if it exists
     * @param string  $route
     * @param Request $request
     * @return mixed
     */
    public static function call(string $route, Request $request): mixed {
    {{#hasRoutes}}
        return match ($route) {
        {{#routes}}
            {{{route}}} => {{className}}::{{method}}({{#hasRequest}}$request{{/hasRequest}}),
            {{#addSpace}}

            {{/addSpace}}
        {{/routes}}

            default => "",
        };
    {{/hasRoutes}}
    {{^hasRoutes}}
        return "";
    {{/hasRoutes}}
    }
}
