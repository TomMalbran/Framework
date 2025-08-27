<?php
namespace Framework\Discovery;

use Attribute;
use ReflectionMethod;

/**
 * The Console Command Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ConsoleCommand {

    public string $name  = "";
    public string $alias = "";

    private ReflectionMethod $handler;



    /**
     * The Console Command Attribute
     * @param string $name
     * @param string $alias Optional.
     */
    public function __construct(string $name, string $alias = "") {
        $this->name  = $name;
        $this->alias = $alias;
    }



    /**
     * Returns the Command Name
     * @return string
     */
    public function getName(): string {
        $result = $this->name;
        if ($this->alias !== "") {
            $result .= " ({$this->alias})";
        }
        return $result;
    }

    /**
     * Checks if the command should be invoked
     * @param string $name
     * @return boolean
     */
    public function shouldInvoke(string $name): bool {
        return $this->name === $name ||
            ($this->alias !== "" && $this->alias === $name);
    }

    /**
     * Sets the Command Handler
     * @param ReflectionMethod $handler
     * @return ConsoleCommand
     */
    public function setHandler(ReflectionMethod $handler): ConsoleCommand {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Invokes the command handler
     * @param mixed ...$args
     * @return mixed
     */
    public function invoke(mixed ...$args): mixed {
        return $this->handler->invoke(null, ...$args);
    }
}
