<?php
namespace Framework\Provider;

use Mustache_Autoloader;
use Mustache_Engine;
use Exception;

/**
 * The Mustache Provider
 */
class Mustache {

    private static ?Mustache_Engine $engine = null;


    /**
     * Returns the Mustache Engine instance
     * @return Mustache_Engine
     */
    private static function getEngine(): Mustache_Engine {
        if (self::$engine === null) {
            Mustache_Autoloader::register();
            self::$engine = new Mustache_Engine();
        }
        return self::$engine;
    }



    /**
     * Validates a Mustache template and returns an error
     * @param string $template
     * @return string
     */
    public static function getError(string $template): string {
        try {
            self::getEngine()->render($template, []);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return "";
    }

    /**
     * Renders a Mustache template
     * @param string              $template
     * @param array<string,mixed> $data
     * @return string
     */
    public static function render(string $template, array $data): string {
        return self::getEngine()->render($template, $data);
    }
}
