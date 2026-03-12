<?php
namespace Framework\Analysis\Enum;

use Framework\Enum\IsEnum;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use Framework\Utils\Strings;

/**
 * The Enum Has None Case Rule
 * @implements Rule<Enum_>
 */
class EnumHasNoneCaseRule implements Rule {

    /**
     * Creates the Model Enum Rule
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Enum_>
     */
    #[\Override]
    public function getNodeType(): string {
        return Enum_::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Enum_ $node
     * @param Scope $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        // Skip if the enum name is missing
        if (!isset($node->namespacedName)) {
            return [];
        }

        // Check if the class exists and is an enum
        $enumName = $node->namespacedName->toString();
        if (!$this->reflectionProvider->hasClass($enumName)) {
            return [];
        }

        // Get the class reflection and check if it is an Enum
        $classReflection = $this->reflectionProvider->getClass($enumName);
        if (!$classReflection->isEnum()) {
            return [];
        }

        // Check for the IsEnum trait
        if (!$classReflection->hasTraitUse(IsEnum::class)) {
            return [];
        }

        // Check if the "None" case exists
        if ($classReflection->hasEnumCase("None")) {
            return [];
        }

        // Generate an error message
        $className = $classReflection->getDisplayName();
        $typeName  = Strings::substringAfter($className, "\\");

        return [
            RuleErrorBuilder::message("Enum '{$typeName}' is missing a 'None' case.")
                ->line($node->getLine())
                ->identifier("framework.enumMissingNone")
                ->build(),
        ];
    }
}
