<?php
namespace Framework\Analysis\General;

use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\ThisType;
use PHPStan\Type\VerbosityLevel;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;

/**
 * The Disallow Class Compare Rule
 * @implements Rule<BinaryOp>
 */
class DisallowClassCompareRule implements Rule {

    /**
     * Create a new Disallow Class Compare Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<BinaryOp>
     */
    #[\Override]
    public function getNodeType(): string {
        return BinaryOp::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param BinaryOp $node
     * @param Scope    $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Target equality, identity, and inequality (GT/LT) operators
        if (!$node instanceof BinaryOp\Equal &&
            !$node instanceof BinaryOp\NotEqual &&
            !$node instanceof BinaryOp\Identical &&
            !$node instanceof BinaryOp\NotIdentical &&
            !$node instanceof BinaryOp\Greater &&
            !$node instanceof BinaryOp\GreaterOrEqual &&
            !$node instanceof BinaryOp\Smaller &&
            !$node instanceof BinaryOp\SmallerOrEqual
        ) {
            return [];
        }

        // Get the types of both sides of the expression
        $leftType  = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        // Allow the comparison if either side is $this
        if ($leftType instanceof ThisType || $rightType instanceof ThisType) {
            return [];
        }

        // Skip if either side is not an object
        if (!$leftType->isObject()->yes() || !$rightType->isObject()->yes()) {
            return [];
        }

        // Allow the comparison if BOTH sides are Enums
        if ($leftType->isEnum()->yes() && $rightType->isEnum()->yes()) {
            return [];
        }


        // Build the error message with the class names
        $leftName  = $leftType->describe(VerbosityLevel::typeOnly());
        $leftName  = Strings::substringAfter($leftName, "\\");
        $rightName = $rightType->describe(VerbosityLevel::typeOnly());
        $rightName = Strings::substringAfter($rightName, "\\");

        return [
            RuleErrorBuilder::message(
                "Direct comparison between classes '$leftName' and '$rightName' is disallowed."
            )
                ->line($node->getLine())
                ->identifier("framework.disallowClassCompare")
                ->build(),
        ];
    }
}
