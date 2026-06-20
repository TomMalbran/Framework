<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Node\InClassMethodNode;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;

/**
 * The Method Return Not Needed Rule
 * @implements Rule<InClassMethodNode>
 */
class MethodReturnNotNeededRule implements Rule {

    /**
     * Create a new Method Return Not Needed Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<InClassMethodNode>
     */
    #[\Override]
    public function getNodeType(): string {
        return InClassMethodNode::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param InClassMethodNode $node
     * @param Scope             $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        $method = $node->getMethodReflection();

        // Check if the method is an override or interface implementation
        $classMethodName = $method->getPrototype()->getDeclaringClass()->getName();
        if ($classMethodName !== $method->getDeclaringClass()->getName()) {
            return [];
        }

        // Access the return type from the first variant
        $variants = $method->getVariants();
        if (!isset($variants[0]) || !$variants[0]->getReturnType()->isBoolean()->yes()) {
            return [];
        }

        $statements = $node->getOriginalNode()->stmts;
        if ($statements === null) {
            return [];
        }

        // Allow methods that only contain a single statement
        if (count($statements) === 1) {
            return [];
        }

        // If no return statements are found, nothing to validate
        $returns = $this->findReturns($statements);
        if (count($returns) === 0) {
            return [];
        }

        $firstValue = null;
        foreach ($returns as $expr) {
            // If any return is not a literal (e.g., a variable), the rule does not apply
            if (!$expr instanceof ConstFetch) {
                return [];
            }

            $name = $expr->name->toLowerString();
            if ($name !== "true" && $name !== "false") {
                return [];
            }

            // Capture the first boolean value to compare against others
            if ($firstValue === null) {
                $firstValue = $name;
                continue;
            }

            // If a different boolean value is found, the method is
            // not "always" returning the same thing
            if ($name !== $firstValue) {
                return [];
            }
        }

        // Build and return the error message
        $methodName = $method->getName();
        return [
            RuleErrorBuilder::message(
                "Method $methodName always returns $firstValue."
            )
                ->line($node->getLine())
                ->identifier("general.methodReturnNotNeeded")
                ->build(),
        ];
    }

    /**
     * Recursively finds all return expressions in the given statements
     * @param array<mixed> $nodes
     * @return list<Node>
     */
    private function findReturns(array $nodes): array {
        $found = [];
        foreach ($nodes as $node) {
            // Standard return
            if ($node instanceof Return_ && $node->expr !== null) {
                $found[] = $node->expr;
            }

            // Handle Switch cases
            if ($node instanceof Switch_) {
                foreach ($node->cases as $case) {
                    $found = array_merge($found, $this->findReturns($case->stmts));
                }
            }

            // Handle Try/Catch/Finally
            if ($node instanceof TryCatch) {
                $found = array_merge($found, $this->findReturns($node->stmts));
                foreach ($node->catches as $catch) {
                    $found = array_merge($found, $this->findReturns($catch->stmts));
                }
                if ($node->finally !== null) {
                    $found = array_merge($found, $this->findReturns($node->finally->stmts));
                }
            }

            if ($node instanceof Stmt) {
                if (property_exists($node, "stmts") && is_array($node->stmts)) {
                    $found = array_merge($found, $this->findReturns($node->stmts));
                }
                if (property_exists($node, "else") && $node->else !== null &&
                    is_object($node->else) && property_exists($node->else, "stmts") &&
                    is_array($node->else->stmts)
                ) {
                    $found = array_merge($found, $this->findReturns($node->else->stmts));
                }
            }
        }
        return $found;
    }
}
