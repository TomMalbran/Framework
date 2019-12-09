<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\File\File;

use Mustache_Autoloader;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Mustache_Exception_UnknownTemplateException;

/**
 * The Mustache Provider
 */
class Mustache {
    
    private static $loaded = false;
    private static $engine = null;
    private static $loader = null;
    
    
    /**
     * Creates a new Mustache Providers
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;

            // Creates a simple engine
            Mustache_Autoloader::register();
            self::$engine = new Mustache_Engine();
            
            // Creates a loader engine
            $path = Framework::getPath(Framework::PublicDir);
            if (File::exists($path)) {
                self::$loader = new Mustache_Engine([
                    "loader" => new Mustache_Loader_FilesystemLoader($path, [ "extension" => ".html" ]),
                ]);
            }
        }
    }
    
    
    
    /**
     * Renders the template using any of the engines depending on the first parameter
     * @param string $templateOrPath
     * @param array  $data
     * @return string
     */
    public static function render(string $templateOrPath, array $data): string {
        self::load();
        if (preg_match('/^[a-z\/]*$/', $templateOrPath)) {
            if (self::$loader != null) {
                return self::$loader->render($templateOrPath, $data);
            }
            return "";
        }
        return self::$engine->render($templateOrPath, $data);
    }
}
