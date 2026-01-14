<?php
namespace Framework\Provider;

use Mustache\Engine;
use Exception;

/**
 * The Mustache Provider
 */
class Mustache {

    private static ?Engine $engine = null;


    /**
     * Returns the Mustache Engine instance
     * @return Engine
     */
    private static function getEngine(): Engine {
        if (self::$engine === null) {
            self::$engine = new Engine();
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
