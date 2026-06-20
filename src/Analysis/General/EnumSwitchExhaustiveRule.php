<?php
namespace Framework\Analysis\General;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\VerbosityLevel;

use PhpParser\Node;
use PhpParser\Node\Stmt\Switch_;

/**
 * The Enum Switch Exhaustive Rule
 * @implements Rule<Switch_>
 */
class EnumSwitchExhaustiveRule implements Rule {

    /**
     * Creates a new Enum Switch Exhaustive Rule
     * @param ReflectionProvider $reflectionProvider
     * @param bool               $enabled            Optional.
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
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

        // Determine the type of the expression inside switch($expr)
        $conditionType     = $scope->getType($node->cond);
        $referencedClasses = $conditionType->getReferencedClasses();

        // Only handle simple cases where exactly one enum class is referenced
        if (count($referencedClasses) !== 1) {
            return [];
        }

        $enumClassName = $referencedClasses[0];
        if (!$this->reflectionProvider->hasClass($enumClassName)) {
            return [];
        }

        // Use reflection to verify if the class is a native PHP Enum
        $classReflection = $this->reflectionProvider->getClass($enumClassName);
        if (!$classReflection->isEnum()) {
            return [];
        }

        $hasDefault       = false;
        $defaultNode      = null;
        $handledCaseNames = [];
        $errors           = [];

        // Iterate through all "case" and "default" blocks in the switch
        foreach ($node->cases as $index => $case) {
            // A null condition ($case->cond) indicates the 'default:' block
            if ($case->cond === null) {
                $hasDefault  = true;
                $defaultNode = $case;
                break;
            }

            // CHECK: Is this case a member of the correct Enum class?
            $caseType    = $scope->getType($case->cond);
            $caseClasses = $caseType->getReferencedClasses();
            $typeName    = $caseType->describe(VerbosityLevel::typeOnly());

            if (count($caseClasses) !== 1 || $caseClasses[0] !== $enumClassName) {
                $errors[] = RuleErrorBuilder::message(
                    "Case type {$typeName} does not match enum {$enumClassName}."
                )
                    ->line($case->getLine())
                    ->identifier("framework.invalidCaseType")
                    ->build();
                continue;
            }

            // Ensure 'None' is always the first case
            if (Strings::endsWith($typeName, "::None") && $index !== 0) {
                $errors[] = RuleErrorBuilder::message(
                    "The 'None' case must be the first case in the switch."
                )
                    ->line($case->getLine())
                    ->identifier("framework.noneCaseMustBeFirst")
                    ->build();
            }

            // Identify which enum member is being handled in this case
            $handledCaseNames[] = $typeName;
        }

        // If there are errors, stop here
        if (count($errors) > 0) {
            return $errors;
        }

        // Compare handled cases against all defined enum cases
        $missingCases = [];
        foreach ($classReflection->getEnumCases() as $enumCase) {
            $caseName = "{$enumClassName}::{$enumCase->getName()}";
            if (!Arrays::contains($handledCaseNames, $caseName)) {
                $missingCases[] = $enumCase->getName();
            }
        }

        // Check if only 'None' is missing and there is a default
        if ($defaultNode !== null && count($missingCases) === 1 &&
            reset($missingCases) === "None"
        ) {
            return [
                RuleErrorBuilder::message(
                    "Switch on {$enumClassName} uses a default for the only missing case 'None'."
                )
                    ->line($defaultNode->getLine())
                    ->identifier("framework.replaceDefaultWithNone")
                    ->build(),
            ];
        }

        // Exhaustiveness check
        if (!$hasDefault && count($missingCases) > 0) {
            $enumClassName = $conditionType->describe(VerbosityLevel::typeOnly());
            $enumName      = Strings::substringAfter($enumClassName, "\\");
            $missingList   = Strings::join($missingCases, ", ");

            return [
                RuleErrorBuilder::message(
                    "Switch on {$enumName} is missing cases: {$missingList}."
                )
                    ->line($node->getLine())
                    ->identifier("framework.switchExhaustive")
                    ->build(),
            ];
        }

        return [];
    }
}
