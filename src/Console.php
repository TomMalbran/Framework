<?php
namespace Framework;

use Framework\Discovery\Discovery;
use Framework\Discovery\ConsoleCommand;
use Framework\Discovery\Priority;
use Framework\Discovery\Package;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Framework Console
 */
class Console {

    /**
     * Runs the Console
     * @return void
     */
    public static function run(): void {
        self::printLogo();
        $commands = self::getCommands();

        // Get the command line arguments
        $argv = is_array($_SERVER["argv"] ?? null) ? $_SERVER["argv"] : [];
        $name = Strings::toString($argv[1] ?? "");
        $args = Arrays::toStrings(array_slice($argv, 2));

        // Try to execute one of the commands
        foreach ($commands as $command) {
            if ($command->shouldInvoke($name)) {
                if (!$command->invoke($args)) {
                    print("Invalid arguments\n");
                    print("  Usage: {$command->getName()} {$command->getArguments()}\n");
                }
                return;
            }
        }

        // Show the usage
        print("Available commands: \n");
        foreach ($commands as $command) {
            print(" - {$command->getName()} {$command->getArguments()}\n");
        }
    }

    /**
     * Displays the version information
     * @return boolean
     */
    #[ConsoleCommand("version", "-v")]
    #[Priority(Priority::Highest)]
    public static function version(): bool {
        $version = Application::getVersion();
        print("Version: $version\n");
        return true;
    }

    /**
     * Installs the Framework
     * @return boolean
     */
    #[ConsoleCommand("install")]
    #[Priority(Priority::High)]
    public static function install(): bool {
        print("Installing the Framework...\n\n");
        $framePath = dirname(__DIR__);
        $appPath   = getcwd();

        if (self::confirm("- Install Framework console command?")) {
            $fromPath = "$framePath/framework";
            $toPath   = "$appPath/framework";
            File::copy($fromPath, $toPath);
            chmod($toPath, 0755);
        }

        if (self::confirm("- Install phpcs file?")) {
            $fromPath = "$framePath/phpcs.xml";
            $toPath   = "$appPath/phpcs.xml";
            File::copy($fromPath, $toPath);
        }

        print("\nInstallation completed.\n");
        return true;
    }



    /**
     * Returns all the Console Commands
     * @return ConsoleCommand[]
     */
    private static function getCommands(): array {
        $frameReflections = Discovery::getReflectionClasses(forFramework: true);
        $appReflections   = Discovery::getReflectionClasses(forFramework: false);
        $reflections      = array_merge($frameReflections, $appReflections);
        $priorities       = [];
        $instances        = [];
        $result           = [];

        foreach ($reflections as $reflection) {
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attributes = $method->getAttributes(ConsoleCommand::class);
                if (!$method->isPublic() || !isset($attributes[0])) {
                    continue;
                }

                $priority = Discovery::getPriority($method);
                if (!isset($instances[$priority])) {
                    $priorities[] = $priority;
                }
                $instances[$priority][] = $attributes[0]->newInstance()->setHandler($method);
            }
        }
        sort($priorities);

        foreach ($priorities as $priority) {
            if (isset($instances[$priority])) {
                foreach ($instances[$priority] as $instance) {
                    $result[] = $instance;
                }
            }
        }
        return $result;
    }

    /**
     * Prints the Framework Logo
     * @return void
     */
    private static function printLogo(): void {
        $version = Package::getVersion();

        echo "\033[33m";
        print(" ______                                           _     \n");
        print("|  ____|                                         | |    \n");
        print("| |__ _ __ __ _ _ __ ___   _____      _____  _ __| | __ \n");
        print("|  __| '__/ _` | '_ ` _ \ / _ \ \ /\ / / _ \| '__| |/ / \n");
        print("| |  | | | (_| | | | | | |  __/\ V  V / (_) | |  |   <  \n");
        print("|_|  |_|  \__,_|_| |_| |_|\___| \_/\_/ \___/|_|  |_|\_\ \n");
        print("$version\n\n");
        echo "\033[0m";
    }

    /**
     * Prompts the user in the console
     * @param string $prompt
     * @return string
     */
    public static function prompt(string $prompt): string {
        $handle = fopen("php://stdin", "r");
        if ($handle === false) {
            return "";
        }

        print("$prompt: ");
        $line = fgets($handle);
        fclose($handle);

        $result = "";
        if (is_string($line)) {
            $result = rtrim($line, "\r\n");
        }
        return $result;
    }

    /**
     * Prompts the user for confirmation in the console
     * @param string $prompt
     * @return boolean
     */
    public static function confirm(string $prompt): bool {
        $response = self::prompt("$prompt (y/n)");
        $response = Strings::toLowerCase($response);
        return $response === "y" || $response === "yes";
    }
}
