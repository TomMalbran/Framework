<?php
namespace Framework\Provider;

use Mustache_Autoloader;
use Mustache_Engine;

/**
 * The Mustache Provider
 */
class Mustache {

    private static ?Mustache_Engine $engine = null;



    /**
     * Renders a mustache template
     * @param string              $template
     * @param array<string,mixed> $data
     * @return string
     */
    public static function render(string $template, array $data): string {
        if (self::$engine === null) {
            Mustache_Autoloader::register();
            self::$engine = new Mustache_Engine();
        }

        return self::$engine->render($template, $data);
    }
}
