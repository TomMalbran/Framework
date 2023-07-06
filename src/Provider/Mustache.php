<?php
namespace Framework\Provider;

use Framework\Framework;
use Framework\File\File;
use Framework\Utils\Strings;

use Mustache_Autoloader;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * The Mustache Provider
 */
class Mustache {

    private static bool             $loaded = false;
    private static ?Mustache_Engine $engine = null;
    private static ?Mustache_Engine $loader = null;


    /**
     * Creates the Mustache Provider
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;

        // Create a simple engine
        Mustache_Autoloader::register();
        self::$engine = new Mustache_Engine();

        // Create a loader engine
        $path = Framework::getPath(Framework::PublicDir);
        if (File::exists($path)) {
            $config  = [ "extension" => ".html" ];
            $loaders = [];

            // Main templates can either be in public or public/templates
            if (File::exists($path, Framework::TemplatesDir)) {
                $loaderPath = File::getPath($path, Framework::TemplatesDir);
                $loaders["loader"] = new Mustache_Loader_FilesystemLoader($loaderPath, $config);
            } else {
                $loaders["loader"] = new Mustache_Loader_FilesystemLoader($path, $config);
            }

            // Partials can be in public/partials or be missing
            if (File::exists($path, Framework::PartialsDir)) {
                $loaderPath = File::getPath($path, Framework::PartialsDir);
                $loaders["partials_loader"] = new Mustache_Loader_FilesystemLoader($loaderPath, $config);
            }

            self::$loader = new Mustache_Engine($loaders);
        }
        return true;
    }



    /**
     * Renders the template using any of the engines depending on the first parameter
     * @param string  $templateOrPath
     * @param array{} $data
     * @return string
     */
    public static function render(string $templateOrPath, array $data): string {
        self::load();
        if (Strings::match($templateOrPath, '/^[a-z\/]*$/')) {
            if (self::$loader != null) {
                return self::$loader->render($templateOrPath, $data);
            }
            return "";
        }
        return self::$engine->render($templateOrPath, $data);
    }
}
