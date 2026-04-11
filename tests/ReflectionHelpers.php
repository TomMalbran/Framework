<?php
namespace Tests;

use ReflectionClass;

trait ReflectionHelpers {

    protected function setPrivateStaticProperty(string $class, string $name, mixed $value): void {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($value);
    }

    protected function setPrivateProperty(object $obj, string $name, mixed $value): void {
        $ref = new ReflectionClass($obj);
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);
    }
}
