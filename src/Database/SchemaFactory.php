<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Database\SchemaModel;
use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Count;
use Framework\Database\Model\Relation;
use Framework\Database\Model\SubRequest;
use Framework\Database\Status\Status;
use Framework\Database\Status\State;
use Framework\File\File;
use Framework\Utils\Strings;

use ReflectionNamedType;

/**
 * The Schema Factory
 */
class SchemaFactory {

    /**
     * Returns all the Schema Models
     * @return SchemaModel[]
     */
    public static function getData(): array {
        $frameModels = self::buildData(forFramework: true);
        $appModels   = self::buildData(forFramework: false);
        return array_merge($frameModels, $appModels);
    }

    /**
     * Builds the Schema Models for the Framework or the Application
     * @param bool $forFramework Optional.
     * @return SchemaModel[]
     */
    public static function buildData(bool $forFramework = false): array {
        $schemaModels = [];
        $modelIDs     = [];
        $dbNames      = [];

        $reflections = Discovery::getReflectionClasses(
            skipIgnored:  true,
            forFramework: $forFramework,
        );

        // Parse the Reflections
        foreach ($reflections as $className => $reflection) {
            $parent        = $reflection->getParentClass();
            $fileName      = "";
            $namespace     = "";
            $fromFramework = false;
            $modelAttr     = null;

            // Get the Data from the Class
            if ($parent === false) {
                $fileName   = $reflection->getFileName();
                $namespace  = $reflection->getNamespaceName();
                $attributes = $reflection->getAttributes(Model::class);
                $modelAttr  = $attributes[0] ?? null;

            // Get some data from the Parent Class
            } else {
                $fileName      = $parent->getFileName();
                $namespace     = $parent->getNamespaceName();
                $parentAttrs   = $parent->getAttributes(Model::class);
                $modelAttr     = $parentAttrs[0] ?? null;
                $fromFramework = true;
            }
            if ($fileName === false || $modelAttr === null) {
                continue;
            }

            /** @var Model */
            $model = $modelAttr->newInstance();

            // Get the Path
            $path  = File::getDirectory($fileName, 1);
            $path  = Strings::stripEnd($path, "/Model");
            $path .= "/Schema";

            // Set the Namespace
            $namespace  = Strings::stripEnd($namespace, "\\Model");
            $namespace .= "\\Schema";

            // Set the Name
            $name = Strings::substringAfter($className, "\\");
            $name = Strings::stripEnd($name, "Model");

            // Get from the Props
            $hasStatus     = false;
            $hasPositions  = false;
            $mainFields    = [];
            $virtualFields = [];
            $expressions   = [];
            $counts        = [];
            $relations     = [];
            $subRequests   = [];
            $states        = [];

            // Parse the Properties
            $props = $reflection->getProperties();
            foreach ($props as $prop) {
                $propType  = $prop->getType();
                $fieldName = $prop->getName();
                if ($propType === null || !$propType instanceof ReflectionNamedType) {
                    continue;
                }

                // Get the Attributes
                $typeName       = $propType->getName();
                $propAttributes = $prop->getAttributes();
                $field          = new Field();
                $relation       = new Relation();
                $subRequest     = new SubRequest();
                $virtual        = null;
                $expression     = null;
                $count          = null;

                if (isset($propAttributes[0])) {
                    $instance = $propAttributes[0]->newInstance();
                    if ($instance instanceof Field) {
                        $field = $instance;
                    } elseif ($instance instanceof Expression) {
                        $expression = $instance;
                    } elseif ($instance instanceof Virtual) {
                        $virtual = $instance;
                    } elseif ($instance instanceof Count) {
                        $count = $instance;
                    } elseif ($instance instanceof Relation) {
                        $relation = $instance;
                    } elseif ($instance instanceof SubRequest) {
                        $subRequest = $instance;
                    }
                }

                // POSITION: If the Name is Position, mark it in the Model
                if ($fieldName === "position") {
                    $hasPositions = true;

                // STATUS: If the Type is Status, mark it in the Model
                } elseif ($typeName === Status::class) {
                    $hasStatus = true;
                    foreach ($propAttributes as $attribute) {
                        $instance = $attribute->newInstance();
                        if ($instance instanceof State) {
                            $states[] = $instance;
                        }
                    }

                // Relation: If the type is not a Built-in Type
                } elseif (!$propType->isBuiltin()) {
                    $relationModelName = Strings::substringAfter($typeName, "\\");
                    $relationModelName = Strings::stripEnd($relationModelName, "Model");
                    $relation->setDataFromAttribute($relationModelName, $fieldName);
                    $relation->parseRelationJoin();
                    $relation->parseOwnerJoin();
                    $relations[] = $relation;

                // SubRequest: If the type is an Array (No SubRequest attribute required)
                } elseif ($typeName === "array") {
                    $comment = $prop->getDocComment();
                    if ($comment !== false) {
                        $subType   = trim(Strings::substringBetween($comment, "@var", "*/"));
                        $subSchema = "";
                        if (Strings::endsWith($subType, "Model[]")) {
                            $subSchema = Strings::stripEnd($subType, "Model[]");
                            $subType   = "";
                        }
                        $subRequests[] = $subRequest->setData($fieldName, $subType, $subSchema);
                    }

                // Expression: If it has an Expression attribute
                } elseif ($expression !== null) {
                    $expressions[] = $expression->setData($fieldName, $typeName);

                // Virtual: If it has a Virtual attribute
                } elseif ($virtual !== null) {
                    $virtualFields[] = $virtual->setData($fieldName, $typeName);

                // Count: If it has a Count attribute
                } elseif ($count !== null) {
                    $counts[] = $count->setData($fieldName);

                // Field: Anything else is a Main Field and can have a Field attribute
                } else {
                    $mainFields[] = $field->setData($fieldName, $typeName);
                }
            }

            // Add the Model
            $schemaModel = new SchemaModel(
                name:          $name,
                path:          $path,
                namespace:     $namespace,
                fromFramework: $fromFramework,
                hasUsers:      $model->hasUsers,
                hasTimestamps: $model->hasTimestamps,
                hasPositions:  $hasPositions,
                hasStatus:     $hasStatus,
                canCreate:     $model->canCreate,
                canEdit:       $model->canEdit,
                canDelete:     $model->canDelete,
                mainFields:    $mainFields,
                virtualFields: $virtualFields,
                expressions:   $expressions,
                relations:     $relations,
                counts:        $counts,
                subRequests:   $subRequests,
                states:        $states,
            );
            $schemaModels[$name] = $schemaModel;

            if ($schemaModel->hasID) {
                $modelIDs[$schemaModel->idName] = $schemaModel->name;
            }
        }

        // Set the Models in the Relations, Counts and SubRequests
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->relations as $relation) {
                if (isset($schemaModels[$relation->relationModelName])) {
                    $relation->setModels($schemaModels[$relation->relationModelName], $schemaModel);
                }
            }
            foreach ($schemaModel->counts as $count) {
                if (isset($schemaModels[$count->modelName])) {
                    $count->setModel($schemaModels[$count->modelName], $schemaModel);
                }
            }
            foreach ($schemaModel->subRequests as $subRequest) {
                if (isset($schemaModels[$subRequest->modelName])) {
                    $subRequest->setModel($schemaModels[$subRequest->modelName], $schemaModel);
                }
            }
        }

        // Set the BelongsTo and the DB Names of the Main Fields
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->mainFields as $field) {
                if (!$field->isID && $field->belongsTo === "") {
                    $field->belongsTo = $modelIDs[$field->name] ?? "";
                }

                // Set the DB Name (Name in uppercase) for the Field when:
                // 1. The main field is an ID of a Model
                if (isset($modelIDs[$field->name])) {
                    $dbNames[$field->name] = $field->setDbName();
                // 2. The field belongs to a Model and the otherField is an ID of a Model
                } elseif ($field->belongsTo !== "" && isset($modelIDs[$field->otherField])) {
                    $dbNames[$field->name] = $field->setDbName();
                // 3. There is a Relation where the Field is the Owner
                } else {
                    foreach ($schemaModel->relations as $relation) {
                        $ownerKey = $relation->ownerFieldName;
                        if ($ownerKey === $field->name && isset($modelIDs[$relation->relationFieldName])) {
                            $dbNames[$field->name] = $field->setDbName();
                            break;
                        }
                    }
                }
            }
            $schemaModel->setIDField();
        }

        // Do the final parsing in the Relations
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->relations as $relation) {
                $relation->generateFields();
                $relation->inferOwnerModelName();
                $relation->setDbNames($dbNames);
            }
        }

        // Generate the Schemas
        $schemas = [];
        foreach ($schemaModels as $modelName => $schemaModel) {
            $schemas[$modelName] = $schemaModel->toArray();
        }
        if (count($schemas) > 0 && Discovery::hasDataFile("schemasOld")) {
            Discovery::saveData("schemasTest", $schemas);
        }

        return $schemaModels;
    }
}
