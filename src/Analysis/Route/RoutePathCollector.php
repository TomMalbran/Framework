<?php
namespace Framework\Analysis\Route;

use Framework\Discovery\Route;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * The Route Path Collector
 * @implements Collector<ClassMethod,array{string,string,string,int}>
 */
class RoutePathCollector implements Collector {

    /**
     * Returns the type of node this collector is interested in
     * @return class-string<ClassMethod>
     */
     #[\Override]
    public function getNodeType(): string {
        return ClassMethod::class;
    }

    /**
     * Processes the node and returns an array of collected data if any
     * @param ClassMethod $node
     * @param Scope       $scope
     * @return array{string,string,string,int}|null
     */
     #[\Override]
    public function processNode(Node $node, Scope $scope): array|null {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        // Find the Route attribute
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() !== Route::class) {
                    continue;
                }

                // Get the path (first argument)
                $pathArg = $attr->args[0] ?? null;
                if ($pathArg === null || !$pathArg->value instanceof String_) {
                    continue;
                }

                return [
                    $pathArg->value->value,
                    "{$classReflection->getName()}::{$node->name->name}",
                    $scope->getFile(),
                    $node->getLine(),
                ];
            }
        }

        return null;
    }
}
