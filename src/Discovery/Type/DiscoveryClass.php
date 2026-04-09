<?php
namespace Framework\Discovery\Type;

use Framework\Discovery\Attr\Priority;
use Framework\Utils\Strings;

use ReflectionClass;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The DiscoveryClass class
 */
class DiscoveryClass {

    /** @var ReflectionClass<object>|null */
    private ?ReflectionClass $reflection = null;

    /** @var list<string> */
    private array $uses = [];



    /**
     * Creates a new DiscoveryClass from a class name and its uses
     * @param ReflectionClass<object>|null $reflection Optional.
     * @param list<string>                 $uses       Optional.
     */
    public function __construct(
        ?ReflectionClass $reflection = null,
        array $uses = [],
    ) {
        $this->reflection = $reflection;
        $this->uses       = $uses;
    }



    /**
     * Returns true if the Class is empty
     * @return bool
      */
    public function isEmpty(): bool {
        return $this->reflection === null;
    }

    /**
     * Returns true if the Class is not empty
     * @return bool
      */
    public function isNotEmpty(): bool {
        return $this->reflection !== null;
    }

    /**
     * Returns the full class name from a use statement
     * @param string $name
     * @return string
     */
    public function getUseClassName(string $name): string {
        foreach ($this->uses as $use) {
            if (Strings::endsWith($use, "\\$name")) {
                return $use;
            }
        }
        return "";
    }

    /**
     * Get the name of the class
     * @return string
     */
    public function getName(): string {
        return $this->reflection?->getName() ?? "";
    }

    /**
     * Returns the parent class of the class
     * @return DiscoveryClass
     */
    public function getParentClass(): DiscoveryClass {
        $result = $this->reflection?->getParentClass();
        if ($result === false || $result === null) {
            return new DiscoveryClass();
        }
        return new DiscoveryClass($result);
    }

    /**
     * Returns the File name of the class
     * @return string
     */
    public function getFileName(): string {
        $result = $this->reflection?->getFileName();
        return $result !== false && $result !== null ? $result : "";
    }

    /**
     * Returns the Namespace name of the class
     * @return string
     */
    public function getNamespaceName(): string {
        return $this->reflection?->getNamespaceName() ?? "";
    }

    /**
     * Returns the attributes of the class
     * @template T of object
     * @param class-string<T> $className
     * @return ReflectionAttribute<T>|null
     */
    public function getAttribute(string $className): ReflectionAttribute|null {
        $attributes = $this->reflection?->getAttributes($className);
        return $attributes[0] ?? null;
    }

    /**
     * Returns the priority of the class
     * @return int
     */
    public function getPriority(): int {
        $attribute = $this->getAttribute(Priority::class);
        if ($attribute !== null) {
            return $attribute->newInstance()->priority;
        }
        return Priority::Normal;
    }

    /**
     * Returns the methods of the class
     * @return list<ReflectionMethod>
     */
    public function getMethods(): array {
        return $this->reflection?->getMethods() ?? [];
    }

    /**
     * Returns the properties of the class, starting from the base class
     * @return list<ReflectionProperty>
      */
    public function getPropertiesBaseFirst(): array {
        if ($this->reflection === null) {
            return [];
        }

        $class  = $this->reflection;
        $result = [];
        do {
            $properties = [];
            foreach ($class->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $class->getName()) {
                    $properties[] = $property;
                }
            }
            $result = array_merge($properties, $result);
            $class  = $class->getParentClass();
        } while ($class !== false);
        return $result;
    }

    /**
     * Creates a new instance of the class
     * @param mixed ...$args
     * @return object|null
     */
    public function newInstance(mixed ...$args): object|null {
        if ($this->reflection === null) {
            return null;
        }
        return $this->reflection->newInstance(...$args);
    }
}
