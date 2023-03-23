<?php
namespace Framework\Route;

use ReflectionClass;

/**
 * The Container Service
 */
class Container {

    /** @var mixed[] */
    private static array $instances = [];

    /** @var string[] */
    private static array $keys      = [];


    /**
     * Resolves the dependencies and creates a new instance of the class
     * @param string $key
     * @param mixed  ...$params
     * @return object
     */
    public static function create(string $key, mixed ...$params): object {
        self::$keys = [];
        return self::resolve($key, false, $params);
    }

    /**
     * Resolves the dependencies and binds a new instance of the class
     * @param string $key
     * @param mixed  ...$params
     * @return object
     */
    public static function bind(string $key, mixed ...$params): object {
        self::$keys = [];
        return self::resolve($key, true, $params);
    }



    /**
     * Resolves the dependencies and saves a new instance of the class
     * @param string  $key
     * @param boolean $save   Optional.
     * @param array{} $params Optional.
     * @return object
     */
    private static function resolve(string $key, bool $save = false, array $params = []): object {
        // If there are too many keys, we are probably in a loop
        self::$keys[] = $key;
        if (count(self::$keys) > 1000) {
            print_r(self::$keys);
            die();
        }

        if (!array_key_exists($key, self::$instances)) {
            $instance = self::buildObject($key, $save, $params);
        } else {
            $instance = self::$instances[$key];
        }
        if ($save) {
            self::$instances[$key] = $instance;
        }
        return $instance;
    }

    /**
     * Instantiates each Class
     * @param string  $className
     * @param boolean $save      Optional.
     * @param array{} $params    Optional.
     * @return object|null
     */
    private static function buildObject(string $className, bool $save = false, array $params = []): ?object {
        $reflector = new ReflectionClass($className);
        $instances = [];

        if (!$reflector->isInstantiable()) {
            return null;
        }

        if ($reflector->getConstructor() !== null) {
            $constructor = $reflector->getConstructor();
            $parameters  = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $parameterType = $parameter->getType();
                $parameterName = $parameterType->getName();
                if (!$parameter->isOptional() && !empty($parameterType) && $parameterName !== "array") {
                    $className = !$parameterType->isBuiltin() ? $parameterType->getName() : null;
                    if ($className !== null) {
                        $instances[] = self::resolve($className, $save);
                    }
                }
            }
        }

        return $reflector->newInstanceArgs(array_merge($instances, $params));
    }
}
