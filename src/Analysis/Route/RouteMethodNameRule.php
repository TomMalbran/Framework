<?php
namespace Framework\Analysis\Route;

use Framework\Discovery\Route;
use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * The Route Method Name Rule
 * @implements Rule<ClassMethod>
 */
class RouteMethodNameRule implements Rule {

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<ClassMethod>
     */
    #[\Override]
    public function getNodeType(): string {
        return ClassMethod::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param ClassMethod $node
     * @param Scope       $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $methodName       = $node->name->name;
        $methodReflection = $classReflection->getNativeMethod($methodName);

        // Find the Route attribute
        $routeAttr  = null;
        $attributes = $methodReflection->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Route::class) {
                $routeAttr = $attribute;
                break;
            }
        }
        if ($routeAttr === null) {
            return [];
        }

        // Extract the path (usually the first positional argument or named 'route')
        $arguments = $routeAttr->getArgumentTypes();
        $pathType  = $arguments["route"] ?? null;
        if ($pathType === null || count($pathType->getConstantStrings()) === 0) {
            return [];
        }

        // Get the last segment of the route
        $routeValue  = $pathType->getConstantStrings()[0]->getValue();
        $routeMethod = Strings::substringAfter($routeValue, "/");

        // Remove route parameters like {id} or {slug} from the segment name if necessary
        if ($methodName === $routeMethod) {
            return [];
        }

        // Build the error message
        $className = $classReflection->getName();
        $method    = "{$className}::{$methodName}()";
        return [
            RuleErrorBuilder::message("Method {$method} must match the route '{$routeMethod}'.")
                ->line($node->getStartLine())
                ->identifier("framework.routeName")
                ->build()
        ];
    }
}
