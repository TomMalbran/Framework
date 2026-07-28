<?php
namespace Framework\Analysis\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;

/**
 * The Action Section Rule
 * @implements Rule<InClassNode>
 */
class ActionSectionRule implements Rule {

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<InClassNode>
     */
    #[\Override]
    public function getNodeType(): string {
        return InClassNode::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param InClassNode $node
     * @param Scope       $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        $classReflection = $node->getClassReflection();
        $classLine       = $node->getOriginalNode()->getStartLine();

        $className   = $classReflection->getName();
        $nativeClass = $classReflection->getNativeReflection();
        $hasSection  = count($nativeClass->getAttributes(Section::class)) > 0;

        $actionLine = null;
        foreach ($nativeClass->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (count($method->getAttributes(Action::class)) === 0) {
                continue;
            }

            $methodStartLine = $method->getStartLine();
            $actionLine      = $classLine;
            if ($methodStartLine !== false) {
                $actionLine = $methodStartLine;
            }
            break;
        }

        // Build the error message
        $hasAction = $actionLine !== null;
        if ($hasSection && !$hasAction) {
            return [
                RuleErrorBuilder::message(
                    "Class {$className} has a #[Section] but no method with #[Action]."
                )
                    ->line($classLine)
                    ->identifier("framework.actionSection")
                    ->build(),
            ];
        }

        if (!$hasSection && $hasAction) {
            return [
                RuleErrorBuilder::message(
                    "Class {$className} has a method with #[Action] but is missing a #[Section]."
                )
                    ->line($actionLine)
                    ->identifier("framework.actionSection")
                    ->build(),
            ];
        }

        return [];
    }
}
