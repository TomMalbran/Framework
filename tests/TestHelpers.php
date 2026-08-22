<?php
// spell-checker: ignore  SFNS
namespace Tests;

use Framework\Application;
use Framework\Core\Configs;
use Framework\Discovery\Type\ComposerData;

use PHPUnit\Framework\AssertionFailedError;

use ReflectionClass;
use ReflectionMethod;

trait TestHelpers {

    /**
     * Returns one case per public method the given class declares, so a method
     * added later is not left untested
     * @param class-string $class
     * @return array<string,array{string}>
     */
    protected static function publicMethodsOf(string $class): array {
        $result = [];
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            // The ones of a parent or a trait belong to whoever tests those
            if ($method->getDeclaringClass()->getName() !== $class || str_starts_with($name, "__")) {
                continue;
            }
            $result[$name] = [ $name ];
        }

        // A provider returning nothing would leave the check unmade
        if (count($result) === 0) {
            throw new AssertionFailedError("The class $class declares no public methods");
        }
        return $result;
    }

    /**
     * Asserts that the file of the running test calls the given method somewhere
     * @param string $method
     * @return void
     */
    protected function assertMethodIsTested(string $method): void {
        $path   = (string)(new ReflectionClass(static::class))->getFileName();
        $source = (string)file_get_contents($path);

        $this->assertMatchesRegularExpression(
            "/\b" . preg_quote($method, "/") . "\s*\(/",
            $source,
            "Nothing in this file calls $method()",
        );
    }

    /**
     * Puts the given Composer Data in place as the one of the App, and hands back
     * the one it replaced, so a test can put that one back when it is done
     * @param ComposerData|null $composer
     * @return ComposerData|null
     */
    protected function swapComposer(?ComposerData $composer): ?ComposerData {
        $was = $this->getPrivateStaticProperty(Application::class, "composer");
        $this->setPrivateStaticProperty(Application::class, "composer", $composer);
        return $was instanceof ComposerData ? $was : null;
    }

    protected function runWithSuppressedWarnings(callable $callback, bool $suppress): mixed {
        if (!$suppress) {
            return $callback();
        }

        set_error_handler(static fn() => true, E_WARNING);
        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }


    protected function getPrivateProperty(object $obj, string $name): mixed {
        $ref = new ReflectionClass($obj);
        $prop = $ref->getProperty($name);
        return $prop->getValue($obj);
    }

    protected function getPrivateStaticProperty(string $class, string $name): mixed {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($name);
        return $prop->getValue();
    }

    protected function setPrivateStaticProperty(string $class, string $name, mixed $value): void {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($name);
        $prop->setValue($value);
    }

    protected function setPrivateProperty(object $obj, string $name, mixed $value): void {
        $ref = new ReflectionClass($obj);
        $prop = $ref->getProperty($name);
        $prop->setValue($obj, $value);
    }

    protected function setConfig(string $key, mixed $value): void {
        // The load overwrites whatever is there, so it has to happen first
        Configs::load();

        /** @var array<string,mixed> */
        $data = $this->getPrivateStaticProperty(Configs::class, "data");
        $data[$key] = $value;
        $this->setPrivateStaticProperty(Configs::class, "data", $data);
    }

    protected function callPrivateStaticMethod(string $class, string $name, mixed ...$args): mixed {
        $ref = new ReflectionClass($class);
        $method = $ref->getMethod($name);
        return $method->invokeArgs(null, $args);
    }


    protected function writeFixtureImage(string $path, int $imgType, int $width, int $height, bool $transparent = false): void {
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);

        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $fillColor = imagecolorallocatealpha($image, 255, 255, 255, 127);
        } else {
            $fillColor = imagecolorallocate($image, 20, 80, 140);
        }

        $this->assertNotFalse($fillColor);
        imagefilledrectangle($image, 0, 0, $width, $height, $fillColor);

        if ($transparent) {
            $dotColor = imagecolorallocatealpha($image, 255, 0, 0, 80);
            $this->assertNotFalse($dotColor);
            imagesetpixel($image, 0, 0, $dotColor);
        }

        match ($imgType) {
            1  => imagegif($image, $path),
            2  => imagejpeg($image, $path, 90),
            3  => imagepng($image, $path),
            15 => imagewbmp($image, $path),
            16 => imagexbm($image, $path),
            default => false,
        };
    }

    protected function findFontFile(): string {
        $candidates = [
            "/System/Library/Fonts/Supplemental/NotoSansLepcha-Regular.ttf",
            "/System/Library/Fonts/Supplemental/Arial.ttf",
            "/System/Library/Fonts/SFNS.ttf",
            "/System/Library/Fonts/Supplemental/Times New Roman.ttf",
            "/Library/Fonts/Arial.ttf",
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $this->markTestSkipped("No TrueType/OpenType font file available for imagettftext");
    }
}
