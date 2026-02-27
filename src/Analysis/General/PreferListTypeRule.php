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
 * The Prefer List Type Rule
 * @implements Rule<Node>
 */
class PreferListTypeRule implements Rule {

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

        // Matches @var, @param, or @return followed by Type[]
        $regExp = '/(@(?:var|param|return)\s+)([a-zA-Z0-9_\|\\\{}<>,]+)\[\]/';
        $match  = preg_match_all($regExp, $text, $matches, PREG_OFFSET_CAPTURE);
        if ($match === false || $match === 0) {
            return [];
        }

        // Iterate over matches and create errors
        foreach ($matches[0] as $index => $fullMatch) {
            if (!isset($matches[2][$index])) {
                continue;
            }
            $type   = $matches[2][$index][0];
            $offset = $fullMatch[1];

            // Calculate the specific line within the docblock
            $linesBeforeMatch = substr_count(substr($text, 0, $offset), "\n");
            $errorLine = $startLine + $linesBeforeMatch;

            $errors[] = RuleErrorBuilder::message("Use 'list<{$type}>' instead of '{$type}[]'.")
                ->line($errorLine)
                ->identifier("framework.preferListType")
                ->build();
        }

        return $errors;
    }
}
