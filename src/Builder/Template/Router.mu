<?php
namespace {{namespace}};

use Framework\Request;
use Framework\Response;
use Framework\System\Access;

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
        return self::getAccessName($route) !== Access::None;
    }

    /**
     * Returns the Access Name for the given Route, if it exists
     * @param string $route
     * @return Access
     */
    public static function getAccessName(string $route): Access {
    {{#hasRoutes}}
        return match ($route) {
        {{#routes}}
            {{{route}}} => Access::{{access}},
            {{#addSpace}}

            {{/addSpace}}
        {{/routes}}

            default => Access::None,
        };
    {{/hasRoutes}}
    {{^hasRoutes}}
        return Access::None;
    {{/hasRoutes}}
    }

    /**
     * Calls the given Route with the given params, if it exists
     * @param string $route
     * @param Request $request
     * @return Response
     */
    public static function call(string $route, Request $request): Response {
    {{#hasRoutes}}
        return match ($route) {
        {{#routes}}
            {{{route}}} => {{className}}::{{method}}({{#hasRequest}}$request{{/hasRequest}}),
            {{#addSpace}}

            {{/addSpace}}
        {{/routes}}

            default => Response::empty(),
        };
    {{/hasRoutes}}
    {{^hasRoutes}}
        return Response::empty();
    {{/hasRoutes}}
    }
}
