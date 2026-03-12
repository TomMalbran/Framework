<?php
namespace Framework\Analysis\Database;

use Framework\Database\Model\Model;
use Framework\Database\Status\Status;
use Framework\Enum\Enum;
use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;

use JsonSerializable;

/**
 * The Model Enum Rule
 * @implements Rule<Property>
 */
class ModelEnumRule implements Rule {

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
     * @return class-string<Property>
     */
    #[\Override]
    public function getNodeType(): string {
        return Property::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Property $node
     * @param Scope    $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        // Check if the class has the #[Model] attribute
        if (count($classReflection->getNativeReflection()->getAttributes(Model::class)) !== 1) {
            return [];
        }

        // Get the property
        if (!isset($node->props[0])) {
            return [];
        }

        $prop       = $node->props[0];
        $nativeProp = $classReflection->getNativeProperty($prop->name->name);
        $propType   = $nativeProp->getReadableType();
        $refClasses = $propType->getReferencedClasses();

        // Get the referenced class name (if it's a single class type)
        if (!isset($refClasses[0])) {
            return [];
        }
        $className = $refClasses[0];

        // Skip the Status class
        if ($className === Status::class) {
            return [];
        }

        // Get the Property type class
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }
        $typeClass = $this->reflectionProvider->getClass($className);

        // Check if the type is an enum and implements both Enum and JsonSerializable interfaces
        if (!$typeClass->isEnum() ||
            $typeClass->implementsInterface(Enum::class) &&
            $typeClass->implementsInterface(JsonSerializable::class)
        ) {
            return [];
        }

        // Generate an error message
        $typeName = Strings::substringAfter($className, "\\");
        return [
            RuleErrorBuilder::message("Enum '{$typeName}' must implement Enum and JsonSerializable.")
                ->line($prop->getStartLine())
                ->identifier("framework.invalidEnumInModel")
                ->build()
        ];
    }
}
