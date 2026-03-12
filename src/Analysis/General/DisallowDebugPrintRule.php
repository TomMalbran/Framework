<?php
namespace Framework\Analysis\General;

use Framework\Utils\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * The Disallow Debug Print Rule
 * @implements Rule<FuncCall>
 */
class DisallowDebugPrintRule implements Rule {

    /**
     * Creates a new Disallow Debug Print Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<FuncCall>
     */
    #[\Override]
    public function getNodeType(): string {
        return FuncCall::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param FuncCall $node
     * @param Scope    $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Only check if the function name is a literal string
        if (!$node->name instanceof Name) {
            return [];
        }

        // Check if the function being called is var_dump or print_r
        $functionName = $scope->resolveName($node->name);
        if (!Arrays::contains([ "var_dump", "print_r" ], $functionName)) {
            return [];
        }

        // Check if the current file is Tester.php
        $filePath = $scope->getFile();
        if (basename($filePath) === "Tester.php") {
            return [];
        }

        // Build and return the error message
        return [
            RuleErrorBuilder::message("Usage of {$functionName}() is disallowed. Remove debug calls.")
            ->line($node->getLine())
            ->identifier("framework.disallowDebugPrint")
            ->build(),
        ];
    }
}
