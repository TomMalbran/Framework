<?php
namespace Framework\Analysis\Enum;

use Framework\Enum\IsEnum;
use Framework\Utils\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;

/**
 * The Enum Internal Methods Rule
 * @implements Rule<Expr>
 */
class EnumInternalMethodsRule implements Rule {

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Expr>
     */
    #[\Override]
    public function getNodeType(): string {
        return Expr::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Expr  $node
     * @param Scope $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        // Only target static calls (from/tryFrom/cases) or constant fetches (::cases)
        if (!$node instanceof StaticCall && !$node instanceof ClassConstFetch) {
            return [];
        }

        // Identify the method or constant name
        $name = "";
        if ($node instanceof StaticCall && $node->name instanceof Node\Identifier) {
            $name = $node->name->toString();
        } elseif ($node instanceof ClassConstFetch && $node->name instanceof Node\Identifier) {
            $name = $node->name->toString();
        }

        // Check if the target is one of the forbidden names
        if (!Arrays::contains([ "from", "tryFrom", "cases" ], $name)) {
            return [];
        }

        // Resolve the class type being called
        if ($node->class instanceof Name) {
            $classType = $scope->resolveTypeByName($node->class);
        } else {
            $classType = $scope->getType($node->class);
        }

        // Ensure we are dealing with an Enum
        if (!$classType->isEnum()->yes()) {
            return [];
        }

        // Allow if the call is made from within the IsEnum trait
        $traitContext = $scope->getTraitReflection();
        if ($traitContext !== null && $traitContext->getName() === IsEnum::class) {
            return [];
        }

        // Generate an error message
        return [
            RuleErrorBuilder::message("Usage of Enum::{$name}() is disallowed. Use methods provided by IsEnum instead.")
            ->line($node->getLine())
            ->identifier("framework.disallowEnumInt")
            ->build(),
        ];
    }
}
