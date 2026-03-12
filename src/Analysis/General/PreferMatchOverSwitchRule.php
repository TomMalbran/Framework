<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;

/**
 * The Prefer Match Over Switch Rule
 * @implements Rule<Switch_>
 */
class PreferMatchOverSwitchRule implements Rule {

    /**
     * Creates a new Prefer Match Over Switch Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Switch_>
     */
    #[\Override]
    public function getNodeType(): string {
        return Switch_::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Switch_ $node
     * @param Scope   $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // If the switch has no cases at all, it's not worth reporting
        if (count($node->cases) === 0) {
            return [];
        }

        // We check every case in the switch to see if it's "simple"
        foreach ($node->cases as $case) {
            // Filter out 'break' statements as they are implicit in 'match'
            $statements = array_filter($case->stmts, static function (Stmt $stmt): bool {
                return !($stmt instanceof Break_);
            });

            // Each case must contain exactly one meaningful statement
            // If a case is empty (fall-through), it is allowed as match handles multiple keys
            if (count($statements) === 0 && $case->cond !== null) {
                continue;
            }

            // Each populated case must contain exactly one meaningful statement
            if (count($statements) !== 1) {
                return [];
            }

            // Check if the statement is a Return or an Assignment Expression
            $singleStmt = reset($statements);
            $isReturn   = $singleStmt instanceof Return_;
            $isAssign   = $singleStmt instanceof Expression
                && $singleStmt->expr instanceof Assign
                && $singleStmt->expr->var instanceof Variable;

            if (!$isReturn && !$isAssign) {
                return [];
            }
        }

        // Build and return the error message
        return [
            RuleErrorBuilder::message("This switch statement can be refactored to a match expression.")
                ->line($node->getLine())
                ->identifier("general.preferMatch")
                ->build(),
        ];
    }
}
