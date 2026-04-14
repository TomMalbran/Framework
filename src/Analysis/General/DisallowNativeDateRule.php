<?php
namespace Framework\Analysis\General;

use Framework\Date\Date;
use Framework\Utils\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * The Disallow Native Date Rule
 * @implements Rule<FuncCall>
 */
class DisallowNativeDateRule implements Rule {

    /**
     * Creates a new Disallow Native Date Rule
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

        // Only check if the function name is a literal string (Name node)
        if (!$node->name instanceof Name) {
            return [];
        }

        // Allow if the current file is Tester.php
        $filePath = $scope->getFile();
        if (basename($filePath) === "Tester.php") {
            return [];
        }

        // Check if the function being called is time or date
        $functionName = $scope->resolveName($node->name);
        if (!Arrays::contains([ "time", "date" ], $functionName)) {
            return [];
        }

        // Check if the current context is the Date class
        $classReflection = $scope->getClassReflection();
        if ($classReflection !== null && $classReflection->getName() === Date::class) {
            return [];
        }

        // Build and return the error message
        return [
            RuleErrorBuilder::message("Usage of {$functionName}() is disallowed. Use the Date class instead.")
            ->line($node->getLine())
            ->identifier("framework.disallowNativeDate")
            ->build(),
        ];
    }
}
