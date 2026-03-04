<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;

/**
 * The Disallow Empty Array Rule
 * @implements Rule<Node>
 */
class DisallowEmptyArrayRule implements Rule {

    /**
     * Create a new Disallow Empty Array Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Node>
     */
    #[\Override]
    public function getNodeType(): string {
        return Node::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Node  $node
     * @param Scope $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        if (!$node instanceof Property &&
            !$node instanceof ClassMethod &&
            !$node instanceof Function_
        ) {
            return [];
        }

        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }

        $text      = $docComment->getText();
        $startLine = $docComment->getStartLine();
        $errors    = [];

        // Match all occurrences of 'array{}' in the doc comment
        preg_match_all("/array\{\}/m", $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $offset           = $match[1];
            $linesBeforeMatch = substr_count(substr($text, 0, $offset), "\n");
            $errorLine        = $startLine + $linesBeforeMatch;

            $errors[] = RuleErrorBuilder::message(
                "Empty array shapes 'array{}' are disallowed." .
                "Use a specific type like 'array<mixed, mixed>' instead."
            )
                ->line($errorLine)
                ->identifier("framework.disallowEmptyArray")
                ->build();
        }

        return $errors;
    }
}
