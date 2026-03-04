<?php
namespace Framework\Analysis\General;

use Framework\Enum\Enum;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\ObjectType;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\PropertyFetch;

/**
 * The Disallow Enum Name Value Rule
 * @implements Rule<PropertyFetch>
 */
class DisallowEnumNameValueRule implements Rule {

    /**
     * Creates a new Disallow Enum Name Value Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<PropertyFetch>
     */
    #[\Override]
    public function getNodeType(): string {
        return PropertyFetch::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param PropertyFetch $node
     * @param Scope         $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Check if the property name is 'name' or 'value'
        if (!$node->name instanceof Identifier) {
            return [];
        }

        $propertyName = $node->name->name;
        if ($propertyName !== "name" && $propertyName !== "value") {
            return [];
        }

        // Get the type of the variable
        $type = $scope->getType($node->var);
        $enumInterfaceType = new ObjectType(Enum::class);

        // If it's not our Enum interface, we don't care
        if (!$enumInterfaceType->isSuperTypeOf($type)->yes()) {
            return [];
        }

        // Allow inside the Enum class itself
        $currentClass = $scope->getClassReflection();
        if ($currentClass !== null) {
            $currentObject = new ObjectType($currentClass->getName());
            if ($enumInterfaceType->isSuperTypeOf($currentObject)->yes()) {
                return [];
            }
        }

        // Otherwise, disallow external access
        return [
            RuleErrorBuilder::message(
                "Accessing '->$propertyName' on Enums is disallowed. Use '->toString()' instead.",
            )
            ->line($node->getLine())
            ->identifier("framework.disallowEnumNameValue")
            ->build(),
        ];
    }
}
