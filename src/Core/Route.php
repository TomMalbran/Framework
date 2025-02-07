<?php
namespace Framework\Core;

use Attribute;

/**
 * The Route Attribute
 */
#[Attribute]
class Route {

    public string $route;
    public string $access;


    /**
     * The Route Attribute
     * @param string $route
     * @param string $access
     */
    public function __construct(string $route, string $access) {
        $this->route  = $route;
        $this->access = $access;
    }
}
