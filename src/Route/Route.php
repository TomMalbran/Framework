<?php
// phpcs:ignoreFile
namespace Framework\Route;

use Attribute;

/**
 * The Route Attribute
 */
#[Attribute]
class Route {

    /**
     * The Route Attribute
     */
    public function __construct(
        public string $route,
        public string $access,
    ) {
    }
}
