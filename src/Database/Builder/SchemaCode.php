<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\ValidateType;
use Framework\Builder\Builder;
use Framework\Date\Type\DateType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Code
 * @phpstan-type Property array{
 *   fieldKey: string,
 *   fieldName: string,
 *   fieldText: string,
 *   fieldDoc: string,
 *   fieldArg: string,
 *   fieldDocNull: string,
 *   fieldArgNull: string,
 *   fieldDocDefault: string,
 *   fieldArgDefault: string,
 *   fieldDocEdit: string,
 *   fieldArgEdit: string,
 *   fieldArgCreate: string,
 *   fieldParam: string,
 *   fieldValue: string,
 *   fieldValueNull: string,
 *   fieldAssign: string,
 *   fieldAssignEdit: string,
 *   fieldIsRequested: bool,
 * }
 */
class SchemaCode {

    /**
     * Returns the Schema code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $mainFields   = $schemaModel->toBuildData("mainFields");
        $expressions  = $schemaModel->toBuildData("expressions");
        $counts       = $schemaModel->toBuildData("counts");
        $relations    = $schemaModel->toBuildData("relations");
        $subRequests  = $schemaModel->toBuildData("subRequests");

        $imports      = self::getImports($schemaModel);
        $fields       = self::getAllFields($schemaModel);
        $hasRequest   = self::hasRequest($fields);
        $uniques      = self::getSomeFields($schemaModel, isUnique: true);
        $parents      = self::getSomeFields($schemaModel, isParent: true);
        $editParents  = $schemaModel->hasPositions ? $parents : [];
        $validations  = self::getValidations($schemaModel);

        $hasVirtual   = count($schemaModel->virtualFields) > 0;
        $hasUniques   = count($uniques) > 0;
        $hasParents   = count($parents) > 0;
        $hasDate      = count(self::getSomeFields($schemaModel, isDate: true)) > 0;
        $hasDateType  = count(self::getSomeFields($schemaModel, isDateType: true)) > 0;
        $hasJsonType  = count(self::getSomeFields($schemaModel, isJsonType: true)) > 0;
        $hasFloatType = count(self::getSomeFields($schemaModel, isFloatType: true)) > 0;
        $queryName    = $schemaModel->queryClass;

        $idType       = $schemaModel->idType->getCodeType($schemaModel->idEnumClass, forEntity: false);
        $idIsEnum     = $schemaModel->idType === FieldType::Enum;
        $idSuffix     = $idIsEnum ? "->toString()" : "";
        $hasIntID     = $schemaModel->hasID && $schemaModel->idType === FieldType::Number;

        $contents     = Builder::render("Schema", [
            "namespace"          => $schemaModel->namespace,
            "name"               => $schemaModel->name,
            "tableName"          => $schemaModel->tableName,
            "entityName"         => Strings::lowerCaseFirst($schemaModel->name),
            "entityClass"        => $schemaModel->entityClass,
            "requestClass"       => $schemaModel->requestClass,
            "columnClass"        => $schemaModel->columnClass,
            "statusClass"        => $schemaModel->statusClass,
            "queryClass"         => $schemaModel->queryClass,

            "hasValidation"      => count($validations) > 0,
            "validations"        => $validations,
            "errorPrefix"        => Strings::toConstantCase($schemaModel->fantasyName) . "_ERROR_",

            "hasID"              => $schemaModel->hasID,
            "hasIntID"           => $hasIntID,
            "hasStringID"        => $schemaModel->hasID && $schemaModel->idType === FieldType::String,
            "hasEnumID"          => $schemaModel->hasID && $idIsEnum,
            "idName"             => $schemaModel->idName,
            "idDbName"           => $schemaModel->idDbName,
            "idValue"            => "\${$schemaModel->idName}$idSuffix",
            "idType"             => $idType,
            "idText"             => Strings::upperCaseFirst($schemaModel->idName),
            "idEnumClass"        => Strings::substringAfter($schemaModel->idEnumClass, "\\"),
            "editType"           => $schemaModel->hasID ? "$queryName|$idType" : $queryName,
            "hasQuery"           => $schemaModel->hasID || count($uniques) > 0,
            "hasList"            => !$schemaModel->skipList,

            "hasPositions"       => $schemaModel->hasPositions,
            "hasPositionsValue"  => $schemaModel->hasPositions ? "true" : "false",
            "hasTimestamps"      => $schemaModel->hasTimestamps,
            "hasTimestampsValue" => $schemaModel->hasTimestamps ? "true" : "false",
            "hasStatus"          => $schemaModel->hasStatus,
            "hasStatusValue"     => $schemaModel->hasStatus ? "true" : "false",
            "hasStatusRequest"   => $schemaModel->isRequested("status"),
            "hasUsers"           => $schemaModel->hasUsers,
            "hasUsersValue"      => $schemaModel->hasUsers ? "true" : "false",
            "hasEncrypt"         => $schemaModel->hasEncrypt(),
            "canCreate"          => $schemaModel->canCreate,
            "canCreateValue"     => $schemaModel->canCreate ? "true" : "false",
            "canEdit"            => $schemaModel->canEdit,
            "canEditValue"       => $schemaModel->canEdit ? "true" : "false",
            "canReplace"         => $schemaModel->canEdit && !$schemaModel->hasAutoInc,
            "canDelete"          => $schemaModel->canDelete,
            "canDeleteValue"     => $schemaModel->canDelete ? "true" : "false",
            "hasImports"         => count($imports) > 0,
            "imports"            => $imports,
            "hasVirtual"         => $hasVirtual,
            "mainFields"         => $mainFields,
            "hasExpressions"     => count($expressions) > 0,
            "expressions"        => $expressions,
            "hasCounts"          => count($counts) > 0,
            "counts"             => $counts,
            "hasRelations"       => count($relations) > 0,
            "relations"          => $relations,
            "hasSubRequests"     => count($subRequests) > 0,
            "subRequests"        => $subRequests,
            "hasRequest"         => $hasRequest,
            "fields"             => $fields,
            "uniques"            => $uniques,
            "hasParents"         => $hasParents,
            "parents"            => $parents,
            "parentsList"        => self::joinFields($parents, "fieldParam"),
            "parentsSecList"     => self::joinFields($parents, "fieldParam", ", "),
            "parentsArgList"     => self::joinFields($parents, "fieldArg", ", "),
            "hasEditParents"     => $schemaModel->hasPositions && $hasParents,
            "editParents"        => $editParents,

            "hasDate"            => $hasDate,
            "hasDateType"        => $hasDateType,
            "hasJsonType"        => $hasJsonType,
            "usesNumbers"        => $hasFloatType || $hasIntID,
            "hasOperator"        => $schemaModel->hasID || $hasUniques || $hasParents,
        ]);
        return Strings::replace($contents, "(, ", "(");
    }

    /**
     * Returns used Imports
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];

        foreach ($schemaModel->fields as $field) {
            if ($field->type === FieldType::Enum) {
                $result[$field->enumClass] = 1;
            }
        }

        foreach ($schemaModel->subRequests as $subRequest) {
            $result["{$subRequest->namespace}\\{$subRequest->modelName}Schema"] = 1;
        }

        foreach ($schemaModel->validates as $validate) {
            if ($validate->typeOf !== "") {
                $result[$validate->typeOf] = 1;
            } elseif ($validate->belongsTo !== "") {
                $result[$validate->belongsTo] = 1;
            }
        }

        $result = array_keys($result);
        sort($result);
        return $result;
    }

    /**
     * Returns a list of all the Fields to set
     * @param SchemaModel $schemaModel
     * @return list<Property>
     */
    private static function getAllFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if (!$field->isAutoInc()) {
                $result[] = self::getField($schemaModel, $field);
            }
        }
        foreach ($schemaModel->extraFields as $field) {
            if ($field->name === "position") {
                $result[] = self::getField($schemaModel, $field);
            }
        }
        return $result;
    }

    /**
     * Returns true if there is at least one Field requested
     * @param list<Property> $fields
     * @return bool
     */
    private static function hasRequest(array $fields): bool {
        foreach ($fields as $field) {
            if ($field["fieldIsRequested"]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a list of Fields with the given property
     * @param SchemaModel $schemaModel
     * @param bool        $isUnique    Optional.
     * @param bool        $isParent    Optional.
     * @param bool        $isDate      Optional.
     * @param bool        $isDateType  Optional.
     * @param bool        $isJsonType  Optional.
     * @param bool        $isFloatType Optional.
     * @return list<Property>
     */
    private static function getSomeFields(
        SchemaModel $schemaModel,
        bool $isUnique = false,
        bool $isParent = false,
        bool $isDate = false,
        bool $isDateType = false,
        bool $isJsonType = false,
        bool $isFloatType = false,
    ): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($isUnique && $field->isUnique) {
                $result[] = self::getField($schemaModel, $field);
            } elseif ($isParent && $field->isParent) {
                $result[] = self::getField($schemaModel, $field);
            } elseif ($isDate && $field->type === FieldType::Date) {
                if ((!$schemaModel->canCreate && $field->name === "createdTime") || $field->name === "modifiedTime") {
                    continue;
                }
                $result[] = self::getField($schemaModel, $field);
            } elseif ($isDateType && $field->dateType !== DateType::None) {
                $result[] = self::getField($schemaModel, $field);
            } elseif ($isJsonType && $field->type === FieldType::JSON) {
                $result[] = self::getField($schemaModel, $field);
            } elseif ($isFloatType && $field->type === FieldType::Float) {
                $result[] = self::getField($schemaModel, $field);
            }
        }
        return $result;
    }

    /**
     * Returns the Fields data for the Schema
     * @param SchemaModel $schemaModel
     * @param Field       $field
     * @return Property
     */
    private static function getField(SchemaModel $schemaModel, Field $field): array {
        $type        = $field->type->getCodeType($field->enumClass, forEntity: false);
        $isDate      = $field->type === FieldType::Date;
        $isEnum      = $field->type === FieldType::Enum;
        $isJSON      = $field->type === FieldType::JSON;

        $typeDoc     = $type;
        $typeNull    = "?$type";
        $canAssign   = !$field->isID && !$field->isParent && !$isDate && !$isEnum;
        $assignDoc   = $canAssign ? "Assign|" : "";

        $default     = FieldType::getDefault($type);
        $defaultNull = $default === "null";
        $docNull     = $defaultNull ? "|null" : "";
        $prefixNull  = $defaultNull ? "?" : "";

        $param       = "\${$field->name}";
        $value       = $param;
        $valueNull   = $param;
        $assign      = $param;
        $assignEdit  = $param;

        if ($isJSON) {
            $typeNull   = "$type|null";
            $typeDoc    = Strings::replace($type, "array", "array<int|string,mixed>");
        } elseif ($type === "float") {
            $assign     = "Numbers::toInt($param, {$field->decimals})";
            $assignEdit = "$param instanceof Assign ? $param : $assign";
        } elseif ($type === "bool") {
            $value      = "$param === true ? 1 : 0";
            $valueNull  = $value;
        }

        return [
            "fieldKey"         => $field->dbName,
            "fieldName"        => $field->name,
            "fieldText"        => Strings::upperCaseFirst($field->name),
            "fieldDoc"         => "$type $param",
            "fieldArg"         => "$type $param",
            "fieldDocNull"     => "$typeDoc|null $param",
            "fieldArgNull"     => "$typeNull $param",
            "fieldDocDefault"  => "$type$docNull $param",
            "fieldArgDefault"  => "$prefixNull$type $param = $default",
            "fieldDocEdit"     => "$assignDoc$typeDoc|null $param",
            "fieldArgEdit"     => ($canAssign ? "Assign|$type|null" : $typeNull) . " $param = null",
            "fieldArgCreate"   => "$typeNull $param = null",
            "fieldParam"       => $param,
            "fieldValue"       => $value,
            "fieldValueNull"   => $valueNull,
            "fieldAssign"      => $assign,
            "fieldAssignEdit"  => $assignEdit,
            "fieldIsRequested" => $schemaModel->isRequested($field->name),
        ];
    }

    /**
     * Summary of joinFields
     * @param list<Property> $fields
     * @param string         $key
     * @param string         $prefix Optional.
     * @return string
     */
    private static function joinFields(array $fields, string $key, string $prefix = ""): string {
        if (count($fields) === 0) {
            return "";
        }
        $list   = Arrays::createArray($fields, $key);
        $result = Strings::join($list, ", ");
        return $prefix . $result;
    }



    /**
     * Returns the Validations for the Schema
     * @param SchemaModel $schemaModel
     * @return list<array<string,mixed>>
     */
    private static function getValidations(SchemaModel $schemaModel): array {
        $hasFromDate = false;
        $result      = [];

        foreach ($schemaModel->validates as $validate) {
            $validation = [];
            switch ($validate->type) {
            case ValidateType::None:
                break;

            case ValidateType::String:
            case ValidateType::Enum:
                $validation = [
                    "isString"     => true,
                    "isRequired"   => $validate->isRequired,
                    "isUnique"     => $validate->isUnique,
                    "typeOf"       => Strings::substringAfter($validate->typeOf, "\\"),
                    "typeInvError" => $validate->getTypeInvalidError(),
                    "method"       => $validate->method,
                    "emptySuffix"  => $validate->isUnique || $validate->typeOf !== "" || $validate->maxLength > 0,
                    "maxLength"    => $validate->maxLength,
                    "fieldName"    => $validate->name,
                    "fieldError"   => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Email:
                $validation = [
                    "isEmail"    => true,
                    "isRequired" => $validate->isRequired,
                    "isUnique"   => $validate->isUnique,
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Url:
                $validation = [
                    "isUrl"      => true,
                    "isRequired" => $validate->isRequired,
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Number:
                $validation = [
                    "isNumber"       => true,
                    "isRequired"     => $validate->isRequired,
                    "isUnique"       => $validate->isUnique,
                    "emptySuffix"    => $validate->isUnique || $validate->isNumeric,
                    "typeOf"         => Strings::substringAfter($validate->typeOf, "\\"),
                    "typeOfError"    => $validate->getTypeOfError(),
                    "belongsTo"      => Strings::substringAfter($validate->belongsTo, "\\"),
                    "belongsToError" => $validate->getBelongsToError(),
                    "method"         => $validate->method,
                    "withParent"     => $validate->withParent,
                    "isNumeric"      => $validate->typeOf === "" && $validate->belongsTo === "",
                    "validFunc"      => $validate->isNumeric ? "isValidNumber" : "isValid",
                    "invalidPrefix"  => $validate->isRequired || $validate->greaterThan !== "",
                    "numericParams"  => $validate->getNumericParams(),
                    "greaterThan"    => $validate->greaterThan,
                    "fieldName"      => $validate->name,
                    "fieldError"     => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Price:
                $validation = [
                    "isPrice"    => true,
                    "isRequired" => $validate->isRequired,
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Date:
                $validation = [
                    "isDate"     => true,
                    "isRequired" => $validate->isRequired,
                    "dateName"   => $validate->dateInput,
                    "hourName"   => $validate->hourInput,
                    "errorText"  => Strings::startsWith($validate->name, "from") ? "FROM" : "TO",
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                if (Strings::startsWith($validate->name, "from")) {
                    $hasFromDate = true;
                } elseif (Strings::startsWith($validate->name, "to") && $hasFromDate) {
                    $validation += [
                        "hasPeriod"    => true,
                        "fromDateName" => "fromTime",
                        "toDateName"   => "toTime",
                    ];
                    $hasFromDate = false;
                }
                break;

                $validation = [
                    "isPrice"    => true,
                    "isRequired" => $validate->isRequired,
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Status:
                $validation = [
                    "isStatus" => true,
                ];
                break;
            }

            $result[] = $validation + [
                "hasIf"     => $validate->if !== "",
                "condition" => $validate->createCondition(),
                "pads"      => $validate->if !== "" ? "    " : "",
            ];
        }
        return $result;
    }
}
