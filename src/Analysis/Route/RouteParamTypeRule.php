<?php
namespace Framework\Analysis\Route;

use Framework\Discovery\Attr\Route;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ExtendedMethodReflection;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * The Route Param Type Rule
 * @implements Rule<ClassMethod>
 */
class RouteParamTypeRule implements Rule {

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

        $methodName = $node->name->name;
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [];
        }

        $methodReflection = $classReflection->getNativeMethod($methodName);

        // Check if the method has the #[Route] attribute
        if (!$this->hasRouteAttribute($methodReflection)) {
            return [];
        }

        // Check the parameters
        $params     = $node->getParams();
        $paramCount = count($params);

        // Allow 0 parameters
        if ($paramCount === 0) {
            return [];
        }

        // If there is exactly 1 parameter, validate it
        if ($paramCount === 1 && isset($params[0])) {
            $param = $params[0];
            $type  = $param->type;

            // Ensure the parameter has a class/interface
            if ($type instanceof Name) {
                $resolvedType = $scope->resolveName($type);

                // Check if class name is "Request" or ends in "Request"
                if (str_ends_with($resolvedType, "Request")) {
                    return [];
                }
            }
        }

        // Build the error message
        $className = $classReflection->getName();
        $method    = "{$className}::{$methodName}()";
        return [
            RuleErrorBuilder::message("Method {$method} must have no params or a Request param since is a Route.")
                ->line($node->getStartLine())
                ->identifier("framework.routeParam")
                ->build()
        ];
    }

    /**
     * Returns true if the given Method has a Route Attribute
     * @param ExtendedMethodReflection $method
     * @return bool
     */
    private function hasRouteAttribute(ExtendedMethodReflection $method): bool {
        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Route::class) {
                return true;
            }
        }
        return false;
    }
}
