<?php
namespace Framework\Analysis\Route;

use Framework\IO\Response;
use Framework\Discovery\Attr\Route;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\NeverType;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * The Route Return Type Rule
 * @implements Rule<ClassMethod>
 */
class RouteReturnTypeRule implements Rule {

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

        // Check the return type
        $responseType = new ObjectType(Response::class);
        $neverType    = new NeverType();

        // getVariants() handles multiple possible signatures
        // (common in built-in PHP, less in user code)
        foreach ($methodReflection->getVariants() as $variant) {
            // Check that the return type is a subtype of Response or Never
            $returnType = $variant->getReturnType();
            if ($responseType->isSuperTypeOf($returnType)->yes() ||
                $neverType->isSuperTypeOf($returnType)->yes()
            ) {
                continue;
            }

            // Build the error message
            $className = $classReflection->getName();
            $method    = "{$className}::{$methodName}()";
            return [
                RuleErrorBuilder::message(
                    "Method {$method} must return a Response since is a Route."
                )
                    ->line($node->getStartLine())
                    ->identifier("framework.routeReturn")
                    ->build()
            ];
        }

        return [];
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
