<?php
namespace Framework\Analysis\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

/**
 * The Action Section Rule
 * @implements Rule<Class_>
 */
class ActionSectionRule implements Rule {

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Class_>
     */
    #[\Override]
    public function getNodeType(): string {
        return Class_::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Class_ $node
     * @param Scope  $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

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
            $actionLine      = $node->getStartLine();
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
                    ->line($node->getStartLine())
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
