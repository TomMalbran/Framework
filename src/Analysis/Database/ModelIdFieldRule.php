<?php
namespace Framework\Analysis\Database;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

/**
 * The Model ID Field Rule
 * @implements Rule<Class_>
 */
class ModelIdFieldRule implements Rule {

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
        // Check if the class has the #[Model] attribute
        if (!$this->isModelClass($node)) {
            return [];
        }

        // Collect all properties marked with #[Field(isID: true)]
        $idFields   = [];
        $lineNumber = $node->getStartLine();

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    if ($attribute->name->toString() !== Field::class) {
                        continue;
                    }

                    // Look for isID: true in the attribute arguments
                    foreach ($attribute->args as $arg) {
                        if ($arg->name?->name === "isID" &&
                            $arg->value instanceof ConstFetch &&
                            $arg->value->name->toLowerString() === "true" &&
                            isset($stmt->props[0])
                        ) {
                            $idFields[] = $stmt->props[0]->name->name;
                            $lineNumber = $stmt->getStartLine();
                        }
                    }
                }
            }
        }

        if (count($idFields) <= 1) {
            return [];
        }

        // Build the error message
        $fields = Strings::join($idFields, ", ");
        return [
            RuleErrorBuilder::message("A model can only have 1 field marked as isID. Found: $fields")
                ->line($lineNumber)
                ->identifier("framework.modelIsID")
                ->build(),
        ];
    }

    /**
     * Checks if a given class node has the #[Model] attribute
     * @param Class_ $classNode
     * @return bool
     */
    public function isModelClass(Class_ $classNode): bool {
        foreach ($classNode->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($attribute->name->toString() === Model::class) {
                    return true;
                }
            }
        }
        return false;
    }
}
