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
     * @param string  $template
     * @param array{} $data
     * @return string
     */
    public static function render(string $template, array $data): string {
        if (empty(self::$engine)) {
            Mustache_Autoloader::register();
            self::$engine = new Mustache_Engine();
        }

        return self::$engine->render($template, $data);
    }
}
