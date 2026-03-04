<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\ConstFetch;

/**
 * The Require Named Bool Arg Rule
 * @implements Rule<CallLike>
 */
class RequireNamedBoolArgRule implements Rule {

    /**
     * Creates a new Require Named Bool Arg Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<CallLike>
     */
    #[\Override]
    public function getNodeType(): string {
        return CallLike::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param CallLike $node
     * @param Scope    $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Only process method, static, or function calls
        if (!$node instanceof MethodCall &&
            !$node instanceof StaticCall &&
            !$node instanceof FuncCall
        ) {
            return [];
        }

        $errors = [];
        foreach ($node->getArgs() as $index => $arg) {
            // Check if the argument value is a literal 'true' or 'false'
            $value = $arg->value;
            if (!$value instanceof ConstFetch) {
                continue;
            }

            $constName = $value->name->toLowerString();
            if ($constName !== "true" && $constName !== "false") {
                continue;
            }

            // If the argument is already named, skip
            if ($arg->name !== null) {
                continue;
            }

            // Report error
            $number   = (int)$index + 1;
            $errors[] = RuleErrorBuilder::message(
                "Boolean argument #$number ($constName) must be named to improve readability" .
                "(e.g., paramName: $constName).",
            )
                ->line($arg->getLine())
                ->identifier("framework.requireNamedBool")
                ->build();
        }

        return $errors;
    }
}
