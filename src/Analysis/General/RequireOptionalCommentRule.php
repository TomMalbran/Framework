<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * The Require Optional Comment Rule
 * @implements Rule<Node>
 */
class RequireOptionalCommentRule implements Rule {

    /**
     * Creates a new Require Optional Comment Rule
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

        if (!$node instanceof ClassMethod &&
            !$node instanceof Function_
        ) {
            return [];
        }

        // Get the DocComment and skip if there is none
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }
        $text = $docComment->getText();

        // Skip the entire check if there are no @param tags in the comment at all
        if (!str_contains($text, "@param")) {
            return [];
        }

        $startLine = $docComment->getStartLine();
        $errors    = [];

        foreach ($node->getParams() as $param) {
            // Check if the parameter has a default value (making it optional)
            if ($param->default === null) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            // Regex checks for the @param tag and uses negative lookahead to ensure "Optional." is missing
            $paramName  = $param->var->name;
            $quotedName = preg_quote($paramName, "/");
            $pattern    = '/@param\s+\S+\s+\$' .  $quotedName . '\s+Optional\./';

            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                // Find the specific line of the @param tag for this variable
                $linePattern = '/@param\s+\S+\s+\$' . $quotedName . '/m';
                $errorLine = $param->getLine();

                if (preg_match($linePattern, $text, $lineMatches, PREG_OFFSET_CAPTURE) === 1) {
                    if (!isset($lineMatches[0])) {
                        continue;
                    }
                    $offset      = $lineMatches[0][1];
                    $linesBefore = substr_count(substr($text, 0, $offset), "\n");
                    $errorLine   = $startLine + $linesBefore;
                }

                $errors[] = RuleErrorBuilder::message(
                    "Optional parameter '\${$paramName}' must have a description starting with 'Optional.'"
                )
                    ->line($errorLine)
                    ->identifier("framework.requireOptional")
                    ->build();
            }
        }

        return $errors;
    }
}
