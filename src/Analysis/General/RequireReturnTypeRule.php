<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ArrowFunction;

/**
 * The Require Return Type Rule
 * @implements Rule<FunctionLike>
 */
class RequireReturnTypeRule implements Rule {

    /**
     * Creates a new Require Return Type Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<FunctionLike>
     */
    #[\Override]
    public function getNodeType(): string {
        return FunctionLike::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param FunctionLike $node
     * @param Scope        $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Skip closures and arrow functions
        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return [];
        }

        if ($node->getReturnType() !== null) {
            return [];
        }

        // Skip constructors and destructors
        if ($node instanceof ClassMethod) {
            $methodName = $node->name->toLowerString();
            if ($methodName === "__construct" || $methodName === "__destruct") {
                return [];
            }
        }

        // Get the description for the error message
        $description = "Function";
        if ($node instanceof ClassMethod) {
            $description = "Method {$node->name->toString()}()";
        } elseif ($node instanceof Function_) {
            $description = "Function {$node->name->toString()}()";
        }

        return [
            RuleErrorBuilder::message("{$description} is missing a native return type hint.")
                ->line($node->getLine())
                ->identifier("framework.requireReturnType")
                ->build(),
        ];
    }
}
