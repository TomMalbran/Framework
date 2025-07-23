<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Database\Structure;
use Framework\Database\SchemaModel;
use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Count;
use Framework\Database\Model\Relation;
use Framework\Database\Model\SubRequest;
use Framework\File\File;
use Framework\System\Status;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

use ReflectionNamedType;

/**
 * The Schema Factory
 */
class SchemaFactory {

    private static ?Dictionary $data = null;

    /** @var Structure[] */
    private static array $structures = [];



    /**
     * Gets the Schema data
     * @return Dictionary
     */
    public static function getData(): Dictionary {
        if (self::$data !== null) {
            return self::$data;
        }

        $schemaModels = self::buildData();

        self::$data = new Dictionary();
        foreach ($schemaModels as $schemaModel) {
            self::$data->set($schemaModel->name, $schemaModel->toArray());
        }
        return self::$data;
    }

    /**
     * Builds the Schema data
     * @param bool $forFramework Optional.
     * @return SchemaModel[]
     */
    public static function buildData(bool $forFramework = false): array {
        $schemaModels = [];
        $modelIDs     = [];

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

                // If the Type is Status, mark it in the Model
                if ($typeName === Status::class) {
                    $hasStatus = true;

                // If the Name is Position, mark it in the Model
                } elseif ($fieldName === "position") {
                    $hasPositions = true;

                // If is not a Built-in Type, is a Relation to be parsed later
                } elseif (!$propType->isBuiltin()) {
                    $relationModelName = Strings::substringAfter($typeName, "\\");
                    $relationModelName = Strings::stripEnd($relationModelName, "Model");
                    $relation->setDataFromAttribute($relationModelName, $fieldName);
                    $relation->parseRelationJoin();
                    $relation->parseOwnerJoin();
                    $relations[] = $relation;

                // If is an Array, is a SubRequest
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

                // If is an Expression, add it to the Model
                } elseif ($expression !== null) {
                    $expressions[] = $expression->setData($fieldName, $typeName);

                // If is a Virtual, add it to the Model
                } elseif ($virtual !== null) {
                    $virtualFields[] = $virtual->setData($fieldName, $typeName);

                // If is a Count, add it to the Model
                } elseif ($count !== null) {
                    $counts[] = $count->setData($fieldName);

                // Add the Main Field
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
            );
            $schemaModels[$name] = $schemaModel;

            if ($schemaModel->hasID) {
                $modelIDs[$schemaModel->idName] = $schemaModel->name;
            }
        }

        // Update the Models using the Models IDs
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->mainFields as $field) {
                if (!$field->isID && $field->belongsTo === "") {
                    $field->belongsTo = $modelIDs[$field->name] ?? "";
                }
                if (isset($modelIDs[$field->name]) || $field->belongsTo !== "") {
                    $field->setDbName();
                } else {
                    foreach ($schemaModel->relations as $relation) {
                        $relationKey = $relation->relationFieldName;
                        $ownerKey    = $relation->ownerFieldName;
                        if ($ownerKey === $field->name && isset($modelIDs[$relationKey])) {
                            $field->setDbName();
                            break;
                        }
                    }
                }
            }
            $schemaModel->setIDField();
        }

        // Parse the Models using the Models created
        foreach ($schemaModels as $schemaModel) {
            // Set the Model of each Relation
            foreach ($schemaModel->relations as $relation) {
                if (isset($schemaModels[$relation->relationModelName])) {
                    $relation->setModels($schemaModels[$relation->relationModelName], $schemaModel);
                    $relation->generateFields();
                }
            }
            foreach ($schemaModel->relations as $relation) {
                $relation->inferOwnerModelName();
            }

            // Set the Model of each Count
            foreach ($schemaModel->counts as $count) {
                if (isset($schemaModels[$count->modelName])) {
                    $count->setModel($schemaModels[$count->modelName]);
                }
            }

            // Set the Model of each SubRequest
            foreach ($schemaModel->subRequests as $subRequest) {
                if (isset($schemaModels[$subRequest->modelName])) {
                    $subRequest->setModel($schemaModels[$subRequest->modelName]);
                }
            }
        }

        // Generate the Schemas
        $schemas = [];
        foreach ($schemaModels as $modelName => $schemaModel) {
            $schemas[$modelName] = $schemaModel->toArray();
        }
        if (count($schemas) > 0) {
            Discovery::saveData("schemasTest", $schemas);
        }

        return $schemaModels;
    }



    /**
     * Creates and Returns the Structure for the given Key
     * @param string $schema
     * @return Structure
     */
    public static function getStructure(string $schema): Structure {
        if (!isset(self::$structures[$schema])) {
            $data = self::getData()->getDict($schema);
            self::$structures[$schema] = new Structure($schema, $data);
        }
        return self::$structures[$schema];
    }
}
