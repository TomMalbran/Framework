<?php
namespace Framework;

use Framework\System\Package;
use Framework\Discovery\Discovery;
use Framework\Discovery\ConsoleCommand;
use Framework\Utils\Strings;
use Framework\Discovery\Priority;

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
        $commands  = self::getCommands();

        $argv      = is_array($_SERVER["argv"] ?? null) ? $_SERVER["argv"] : [];
        $name      = Strings::toString($argv[1] ?? "");
        $canDelete = ($argv[2] ?? "") === "--delete";

        // Try to execute one of the commands
        foreach ($commands as $command) {
            if ($command->shouldInvoke($name)) {
                $command->invoke($canDelete);
                return;
            }
        }

        // Show the usage
        echo "Available commands: \n";
        foreach ($commands as $command) {
            echo " - {$command->getName()}\n";
        }
    }

    /**
     * Displays the version information
     * @return boolean
     */
    #[ConsoleCommand("version", "-v")]
    #[Priority(Priority::Highest)]
    public static function version(): bool {
        print("Version: " . Package::Version . "\n");
        return true;
    }



    /**
     * Returns all the Console Commands
     * @return ConsoleCommand[]
     */
    private static function getCommands(): array {
        $frameReflections = Discovery::getReflectionClasses(skipIgnored: true, forFramework: true);
        $appReflections   = Discovery::getReflectionClasses(skipIgnored: true, forFramework: false);
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
            foreach ($instances[$priority] as $instance) {
                $result[] = $instance;
            }
        }
        return $result;
    }
}
