<?php
namespace Framework\Discovery;

use Framework\System\Access;

use Attribute;

/**
 * The Route Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route {

    public string $route;
    public Access $access;



    /**
     * The Route Attribute
     * @param string $route
     * @param Access $access
     */
    public function __construct(string $route, Access $access) {
        $this->route  = $route;
        $this->access = $access;
    }
}
