<?php
namespace Framework\Analysis\Signal;

use Framework\Discovery\Attr\Listener;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * The Listener Return Void Rule
 * @implements Rule<ClassMethod>
 */
class ListenerReturnVoidRule implements Rule {

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

        // Find the Listener attribute
        $listenerAttr = null;
        $attributes   = $methodReflection->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Listener::class) {
                $listenerAttr = $attribute;
                break;
            }
        }
        if ($listenerAttr === null) {
            return [];
        }

        // Check if the return type is missing or is not explicitly 'void'
        $returnType = $node->returnType;
        if ($returnType === null ||
            !($returnType instanceof Identifier && $returnType->toLowerString() === "void")
        ) {
            return [
                RuleErrorBuilder::message(
                    "Method with #[Listener] attribute must return 'void'."
                )
                    ->line($node->getLine())
                    ->identifier("framework.listenerVoid")
                    ->build(),
            ];
        }

        return [];
    }
}
