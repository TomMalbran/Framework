<?php
namespace Framework\Analysis\Database;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Status\Status;
use Framework\Utils\Strings;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\UnionType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

/**
 * The Model Unique Field Rule
 * @implements Rule<Class_>
 */
class ModelUniqueFieldRule implements Rule {

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

        $idFields       = [];
        $positionFields = [];
        $statusFields   = [];

        $idLineNumber       = $node->getStartLine();
        $positionLineNumber = $node->getStartLine();
        $statusLineNumber   = $node->getStartLine();

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Property || !isset($stmt->props[0])) {
                continue;
            }

            foreach ($stmt->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    if ($attribute->name->toString() !== Field::class) {
                        continue;
                    }

                    foreach ($attribute->args as $arg) {
                        // Look for isID: true in the attribute arguments
                        if ($arg->name?->name === "isID" &&
                            $arg->value instanceof ConstFetch &&
                            $arg->value->name->toLowerString() === "true"
                        ) {
                            $idFields[]   = $stmt->props[0]->name->name;
                            $idLineNumber = $stmt->getStartLine();
                        }

                        // Look for isPosition: true in the attribute arguments
                        if ($arg->name?->name === "isPosition" &&
                            $arg->value instanceof ConstFetch &&
                            $arg->value->name->toLowerString() === "true"
                        ) {
                            $positionFields[]   = $stmt->props[0]->name->name;
                            $positionLineNumber = $stmt->getStartLine();
                        }
                    }
                }
            }

            // Check if the property type is Status using AST + Scope resolution
            if ($stmt->type !== null) {
                // Flat names, multi-unions, and intersections are mapped safely to flat names here
                $typesToInspect = $this->extractNamesFromType($stmt->type);

                foreach ($typesToInspect as $typeNode) {
                    // Resolve the short name or imported alias into its absolute FQN type
                    $resolvedType = $scope->resolveTypeByName($typeNode);

                    if ($resolvedType->getObjectClassNames() !== [] &&
                        in_array(Status::class, $resolvedType->getObjectClassNames(), strict: true)
                    ) {
                        $statusFields[]   = $stmt->props[0]->name->name;
                        $statusLineNumber = $stmt->getStartLine();
                        break;
                    }
                }
            }
        }

        // Build the error message
        $errors = [];

        if (count($idFields) > 1) {
            $fields   = Strings::join($idFields, ", ");
            $errors[] = RuleErrorBuilder::message("A model can only have 1 field marked as isID. Found: $fields")
                ->line($idLineNumber)
                ->identifier("framework.modelID")
                ->build();
        }

        if (count($positionFields) > 1) {
            $fields   = Strings::join($positionFields, ", ");
            $errors[] = RuleErrorBuilder::message("A model can only have 1 field marked as isPosition. Found: $fields")
                ->line($positionLineNumber)
                ->identifier("framework.modelPosition")
                ->build();
        }

        if (count($statusFields) > 1) {
            $fields   = Strings::join($statusFields, ", ");
            $errors[] = RuleErrorBuilder::message("A model can only have 1 field using the Status type. Found: $fields")
                ->line($statusLineNumber)
                ->identifier("framework.modelStatus")
                ->build();
        }

        return $errors;
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

    /**
     * Helper to recursively extract Name identifier nodes out of raw property type structures
     * @param Node $typeNode
     * @return list<Name>
     */
    private function extractNamesFromType(Node $typeNode): array {
        if ($typeNode instanceof Name) {
            return [ $typeNode ];
        }

        if ($typeNode instanceof Identifier) {
            return [ new Name($typeNode->toString()) ];
        }

        if ($typeNode instanceof UnionType || $typeNode instanceof IntersectionType) {
            $names = [];
            foreach ($typeNode->types as $subType) {
                $names = array_merge($names, $this->extractNamesFromType($subType));
            }
            return $names;
        }

        return [];
    }
}
