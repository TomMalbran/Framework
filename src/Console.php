<?php
namespace Framework;

use Framework\Discovery\Discovery;
use Framework\Discovery\ConsoleCommand;
use Framework\Discovery\Priority;
use Framework\Discovery\Package;
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
        echo "FRAMEWORK\n";
        $commands = self::getCommands();

        $argv = is_array($_SERVER["argv"] ?? null) ? $_SERVER["argv"] : [];
        $name = Strings::toString($argv[1] ?? "");
        $args = Arrays::toStrings(array_slice($argv, 2));

        // Try to execute one of the commands
        foreach ($commands as $command) {
            if ($command->shouldInvoke($name)) {
                if (!$command->invoke($args)) {
                    echo "Invalid arguments\n";
                    echo "  Usage: {$command->getName()} {$command->getArguments()}\n";
                }
                return;
            }
        }

        // Show the usage
        echo "Available commands: \n";
        foreach ($commands as $command) {
            echo " - {$command->getName()} {$command->getArguments()}\n";
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
}
