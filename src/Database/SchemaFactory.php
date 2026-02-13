<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Discovery\Package;
use Framework\Database\SchemaModel;
use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Validate;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Count;
use Framework\Database\Model\Relation;
use Framework\Database\Model\SubRequest;
use Framework\Database\Status\Status;
use Framework\Database\Status\State;
use Framework\File\File;
use Framework\Enum\Enum;
use Framework\Utils\Strings;

use Throwable;
use JsonSerializable;
use ReflectionClass;
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
        $reflections  = Discovery::getReflectionClasses(forFramework: $forFramework);
        $errors       = [];
        $schemaModels = [];
        $modelIDs     = [];
        $dbNames      = [];

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


            // Get the Path
            $path  = File::getDirectory($fileName, 1);
            $path  = Strings::stripEnd($path, "/" . Package::ModelDir);
            $path .= "/" . Package::SchemaDir;

            // Get the Namespace
            $namespace = Strings::stripEnd($namespace, "\\" . Package::ModelDir);
            $namespace = "$namespace\\" . Package::SchemaDir;

            // Get the Model Name
            $modelName = Strings::substringAfter($className, "\\");
            $modelName = Strings::stripEnd($modelName, "Model");


            // Instantiate the Model
            try {
                /** @var Model */
                $model = $modelAttr->newInstance();
            } catch (Throwable $e) {
                $errors[] = "$modelName: Model attribute could not be instantiated ($fileName)";
                continue;
            }

            // Get the Fantasy Name
            $fantasyName = $model->fantasyName;
            if ($fantasyName === "") {
                $fantasyName = $modelName;
            }

            // Get data from the Properties
            $hasStatus     = false;
            $hasPositions  = false;
            $usesRequest   = false;
            $mainFields    = [];
            $validates     = [];
            $virtualFields = [];
            $expressions   = [];
            $counts        = [];
            $relations     = [];
            $subRequests   = [];
            $states        = [];

            // Parse the Properties
            $props = Discovery::getPropertiesBaseFirst($reflection);
            foreach ($props as $prop) {
                $propType  = $prop->getType();
                $fieldName = $prop->getName();
                if ($propType === null || !$propType instanceof ReflectionNamedType) {
                    continue;
                }

                // Get the Type
                $typeName     = $propType->getName();
                $isFieldClass = FieldType::isValidClass($typeName);
                $isStatus     = $typeName === Status::class;
                $isArray      = $typeName === "array";
                $isEnum       = false;
                $isModel      = false;

                if (!$isFieldClass && !$isStatus && !$propType->isBuiltin()) {
                    [ $isEnum, $isValidEnum ] = self::isPropEnum($typeName, $modelName, $errors);
                    if ($isEnum && !$isValidEnum) {
                        continue 2;
                    }
                    $isModel = !$isEnum;
                }

                // Get the Attributes
                $propAttributes = $prop->getAttributes();
                $field          = new Field();
                $relation       = new Relation();
                $subRequest     = new SubRequest();
                $validate       = null;
                $virtual        = null;
                $expression     = null;
                $count          = null;

                foreach ($propAttributes as $propAttribute) {
                    try {
                        $instance = $propAttribute->newInstance();
                    } catch (Throwable $e) {
                        $errors[] = "$modelName: $fieldName attribute could not be instantiated ($fileName)";
                        continue 2;
                    }

                    if ($instance instanceof Field) {
                        $field = $instance;
                    } elseif ($instance instanceof Validate) {
                        $validate = $instance;
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


                // POSITION: If the Name is Position, mark it in the Model.
                if ($fieldName === "position") {
                    $hasPositions = true;

                // STATUS: If the Type is Status, mark it in the Model.
                } elseif ($isStatus) {
                    $hasStatus = true;
                    foreach ($propAttributes as $attribute) {
                        $instance = $attribute->newInstance();
                        if ($instance instanceof State) {
                            $states[] = $instance;
                        }
                    }
                    if ($validate !== null) {
                        $validates[] = $validate->setStatus();
                    }

                // RELATION: If the type is a class representing a Model.
                } elseif ($isModel) {
                    $relationModelName = Strings::substringAfter($typeName, "\\");
                    $relationModelName = Strings::stripEnd($relationModelName, "Model");
                    $relation->setDataFromAttribute($relationModelName, $fieldName);
                    $relation->parseRelationJoin();
                    $relation->parseOwnerJoin();
                    $relations[] = $relation;

                // SUB-REQUEST: If the type is an Array (No SubRequest attribute required).
                } elseif ($isArray) {
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

                // EXPRESSION: If it has an Expression attribute.
                } elseif ($expression !== null) {
                    $expressions[] = $expression->setData($fieldName, $typeName);

                // VIRTUAL: If it has a Virtual attribute.
                } elseif ($virtual !== null) {
                    $virtualFields[] = $virtual->setData($fieldName, $typeName, $isEnum);

                // COUNT: If it has a Count attribute.
                } elseif ($count !== null) {
                    $counts[] = $count->setData($fieldName);

                // FIELD: Anything else is a Main Field and can have a Field attribute.
                // VALIDATE: A Main Field can also have a Validate attribute.
                } else {
                    if ($field->fromRequest) {
                        $usesRequest = true;
                    }
                    $mainField    = $field->setData($fieldName, $typeName, $isEnum);
                    $mainFields[] = $mainField;

                    if ($validate !== null) {
                        $validates[] = $validate->setField($mainField, $fantasyName);
                    }
                }
            }

            // Add the Model
            $schemaModel = new SchemaModel(
                name:          $modelName,
                fantasyName:   $fantasyName,
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
                usesRequest:   $usesRequest,
                mainFields:    $mainFields,
                validates:     $validates,
                virtualFields: $virtualFields,
                expressions:   $expressions,
                relations:     $relations,
                counts:        $counts,
                subRequests:   $subRequests,
                states:        $states,
            );
            $schemaModels[$modelName] = $schemaModel;

            if ($schemaModel->hasID) {
                $modelIDs[$schemaModel->idName] = $modelName;
            }
        }


        // Set the Models in the Relations, Counts and SubRequests
        self::setModels($schemaModels);

        // Set the BelongsTo and the DB Names of the Main Fields
        self::parseMainFields($schemaModels, $modelIDs, $dbNames);

        // Do the final parsing in the Relations
        self::parseRelations($schemaModels, $dbNames);


        // Show the Models with Errors
        if (count($errors) > 0) {
            print("\nMODELS WITH ERROR:\n");
            foreach ($errors as $error) {
                print("- $error\n\n");
            }
            print("\n");
        }

        return $schemaModels;
    }



    /**
     * Returns true if the Enum and if is valid
     * @param string   $typeName
     * @param string   $modelName
     * @param string[] $errors
     * @return array{bool,bool}
     */
    private static function isPropEnum(string $typeName, string $modelName, array &$errors): array {
        if (!class_exists($typeName)) {
            return [ false, false ];
        }
        $propClass = new ReflectionClass($typeName);
        if (!$propClass->isEnum()) {
            return [ false, false ];
        }

        // Validate the Enum
        $propFileName  = $propClass->getFileName();
        $propClassName = Strings::substringAfter($typeName, "\\");
        $isValid       = true;

        if (!$propClass->implementsInterface(Enum::class)) {
            $isValid  = false;
            $errors[] = "$modelName: $propClassName enum must implement the Enum class ($propFileName)";
        } elseif (!$propClass->implementsInterface(JsonSerializable::class)) {
            $isValid  = false;
            $errors[] = "$modelName: $propClassName enum must implement JsonSerializable ($propFileName)";
        }
        return [ true, $isValid ];
    }

    /**
     * Set the Models in the Relations, Counts and SubRequests
     * @param array<string,SchemaModel> $schemaModels
     * @return void
     */
    private static function setModels(array $schemaModels): void {
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
    }

    /**
     * Set the BelongsTo and the DB Names of the Main Fields
     * @param array<string,SchemaModel> $schemaModels
     * @param array<string,string>      $modelIDs
     * @param array<string,string>      $dbNames
     * @return void
     */
    private static function parseMainFields(array $schemaModels, array $modelIDs, array &$dbNames): void {
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
    }

    /**
     * Final parsing of the Relations
     * @param array<string,SchemaModel> $schemaModels
     * @param array<string,string>      $dbNames
     * @return void
     */
    private static function parseRelations(array $schemaModels, array $dbNames): void {
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->relations as $relation) {
                $relation->generateFields();
                $relation->inferOwnerModelName();
                $relation->setDbNames($dbNames);
            }
        }
    }
}
