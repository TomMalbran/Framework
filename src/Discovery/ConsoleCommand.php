<?php
namespace Framework\Discovery;

use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use Attribute;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * The Console Command Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ConsoleCommand {

    public string $name  = "";
    public string $alias = "";

    private ?ReflectionMethod $handler = null;



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
     * Returns the Command Arguments
     * @return string
     */
    public function getArguments(): string {
        if ($this->handler === null) {
            return "";
        }

        $params    = $this->handler->getParameters();
        $argValues = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === "bool") {
                $name = "--$name";
            } else {
                $name = "--$name=<value>";
            }
            $argValues[] = $name;
        }
        return Strings::join($argValues, " ");
    }

    /**
     * Checks if the command should be invoked
     * @param string $name
     * @return bool
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
     * @param string[] $arguments
     * @return bool
     */
    public function invoke(array $arguments): bool {
        if ($this->handler === null) {
            return false;
        }

        // Parse the Arguments
        $argsData = [];
        foreach ($arguments as $argument) {
            $name  = Strings::substringBefore($argument, "=");
            $name  = Strings::stripStart($name, "--");
            $name  = Strings::toLowerCase($name);

            $value = true;
            if (Strings::contains($argument, "=")) {
                $value = Strings::substringAfter($argument, "=");
                if (Numbers::isValid($value)) {
                    $value = (int)$value;
                } elseif (Strings::toLowerCase($value) === "true") {
                    $value = true;
                } elseif (Strings::toLowerCase($value) === "false") {
                    $value = false;
                }
            }
            $argsData[$name] = $value;
        }

        // Match the Arguments to the Parameters
        $params    = $this->handler->getParameters();
        $minParams = 0;
        $argValues = [];
        foreach ($params as $param) {
            $name    = $param->getName();
            $nameIdx = Strings::toLowerCase($name);
            if (isset($argsData[$nameIdx])) {
                $argValues[$name] = $argsData[$nameIdx];
            }
            if (!$param->isOptional()) {
                $minParams += 1;
            }
        }

        // Check if we have enough arguments
        if (count($argValues) < $minParams) {
            return false;
        }

        $this->handler->invokeArgs(null, $argValues);
        return true;
    }
}
