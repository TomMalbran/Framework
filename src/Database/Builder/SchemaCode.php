<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\ValidateType;
use Framework\Builder\Builder;
use Framework\Date\DateType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Code
 */
class SchemaCode {

    /**
     * Returns the Schema code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $mainFields  = $schemaModel->toBuildData("mainFields");
        $expressions = $schemaModel->toBuildData("expressions");
        $counts      = $schemaModel->toBuildData("counts");
        $relations   = $schemaModel->toBuildData("relations");
        $subRequests = $schemaModel->toBuildData("subRequests");

        $imports     = self::getImports($schemaModel);
        $fields      = self::getAllFields($schemaModel);
        $uniques     = self::getSomeFields($schemaModel, isUnique: true);
        $parents     = self::getSomeFields($schemaModel, isParent: true);
        $editParents = $schemaModel->hasPositions ? $parents : [];
        $validations = self::getValidations($schemaModel);

        $hasVirtual  = count($schemaModel->virtualFields) > 0;
        $hasUniques  = count($uniques) > 0;
        $hasParents  = count($parents) > 0;
        $hasDate     = count(self::getSomeFields($schemaModel, isDate: true)) > 0;
        $hasDateType = count(self::getSomeFields($schemaModel, isDateType: true)) > 0;
        $queryName   = $schemaModel->queryClass;

        $idType      = FieldType::getCodeType($schemaModel->idType, $schemaModel->idEnumClass, false);
        $idIsEnum    = $schemaModel->idType === FieldType::Enum;
        $idSuffix    = $idIsEnum ? "->name" : "";
        $idConvert   = $idIsEnum ? "string" : $idType;

        $contents    = Builder::render("Schema", [
            "namespace"           => $schemaModel->namespace,
            "name"                => $schemaModel->name,
            "table"               => $schemaModel->tableName,
            "entityClass"         => $schemaModel->entityClass,
            "columnClass"         => $schemaModel->columnClass,
            "statusClass"         => $schemaModel->statusClass,
            "queryClass"          => $schemaModel->queryClass,

            "hasValidation"       => count($validations) > 0,
            "validations"         => $validations,
            "validatesColor"      => self::validatesColor($schemaModel),
            "errorPrefix"         => Strings::pascalCaseToUpperCase($schemaModel->fantasyName) . "_ERROR_",

            "hasID"               => $schemaModel->hasID,
            "hasIntID"            => $schemaModel->hasID && $schemaModel->idType === FieldType::Number,
            "hasStringID"         => $schemaModel->hasID && $schemaModel->idType === FieldType::String,
            "hasEnumID"           => $schemaModel->hasID && $idIsEnum,
            "idName"              => $schemaModel->idName,
            "idDbName"            => $schemaModel->idDbName,
            "idValue"             => "\${$schemaModel->idName}$idSuffix",
            "idType"              => $idType,
            "idText"              => Strings::upperCaseFirst($schemaModel->idName),
            "idEnumClass"         => Strings::substringAfter($schemaModel->idEnumClass, "\\"),

            "editType"            => $schemaModel->hasID ? "$queryName|$idType" : $queryName,
            "convertType"         => $schemaModel->hasID ? "Query|$idConvert" : "Query",
            "convertValue"        => "\$value$idSuffix",

            "hasPositions"        => $schemaModel->hasPositions,
            "hasPositionsValue"   => $schemaModel->hasPositions ? "true" : "false",
            "hasTimestamps"       => $schemaModel->hasTimestamps,
            "hasTimestampsValue"  => $schemaModel->hasTimestamps ? "true" : "false",
            "hasStatus"           => $schemaModel->hasStatus,
            "hasStatusValue"      => $schemaModel->hasStatus ? "true" : "false",
            "hasUsers"            => $schemaModel->hasUsers,
            "hasUsersValue"       => $schemaModel->hasUsers ? "true" : "false",
            "hasEncrypt"          => $schemaModel->hasEncrypt(),
            "canCreate"           => $schemaModel->canCreate,
            "canCreateValue"      => $schemaModel->canCreate ? "true" : "false",
            "canEdit"             => $schemaModel->canEdit,
            "canEditValue"        => $schemaModel->canEdit ? "true" : "false",
            "canReplace"          => $schemaModel->canEdit && !$schemaModel->hasAutoInc,
            "canDelete"           => $schemaModel->canDelete,
            "canDeleteValue"      => $schemaModel->canDelete ? "true" : "false",
            "usesRequest"         => $schemaModel->usesRequest,
            "hasImports"          => count($imports) > 0,
            "imports"             => $imports,
            "hasVirtual"          => $hasVirtual,
            "mainFields"          => $mainFields,
            "hasExpressions"      => count($expressions) > 0,
            "expressions"         => $expressions,
            "hasCounts"           => count($counts) > 0,
            "counts"              => $counts,
            "hasRelations"        => count($relations) > 0,
            "relations"           => $relations,
            "hasSubRequests"      => count($subRequests) > 0,
            "hasSubRequestsValue" => count($subRequests) > 0 ? "true" : "false",
            "subRequests"         => $subRequests,
            "fields"              => $fields,
            "uniques"             => $uniques,
            "hasParents"          => $hasParents,
            "parents"             => $parents,
            "parentsList"         => self::joinFields($parents, "fieldParam"),
            "parentsSecList"      => self::joinFields($parents, "fieldParam", ", "),
            "parentsArgList"      => self::joinFields($parents, "fieldArg", ", "),
            "hasEditParents"      => $schemaModel->hasPositions && $hasParents,
            "editParents"         => $editParents,
            "hasDate"             => $hasDate,
            "hasDateType"         => $hasDateType,
            "hasQueryOperator"    => $schemaModel->hasID || $hasUniques || $hasParents,
        ]);
        return Strings::replace($contents, "(, ", "(");
    }

    /**
     * Returns used Imports
     * @param SchemaModel $schemaModel
     * @return string[]
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
     * @return array<string,string>[]
     */
    private static function getAllFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if (!$field->isAutoInc()) {
                $result[] = self::getField($field);
            }
        }
        foreach ($schemaModel->extraFields as $field) {
            if ($field->name === "position") {
                $result[] = self::getField($field);
            }
        }
        return $result;
    }

    /**
     * Returns a list of Fields with the given property
     * @param SchemaModel $schemaModel
     * @param bool        $isUnique    Optional.
     * @param bool        $isParent    Optional.
     * @param bool        $isDate      Optional.
     * @param bool        $isDateType  Optional.
     * @return array<string,string>[]
     */
    private static function getSomeFields(
        SchemaModel $schemaModel,
        bool $isUnique = false,
        bool $isParent = false,
        bool $isDate = false,
        bool $isDateType = false,
    ): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($isUnique && $field->isUnique) {
                $result[] = self::getField($field);
            } elseif ($isParent && $field->isParent) {
                $result[] = self::getField($field);
            } elseif ($isDateType && $field->dateType !== DateType::None) {
                $result[] = self::getField($field);
            } elseif ($isDate && $field->type === FieldType::Date) {
                if ((!$schemaModel->canCreate && $field->name === "createdTime") || $field->name === "modifiedTime") {
                    continue;
                }
                $result[] = self::getField($field);
            }
        }
        return $result;
    }

    /**
     * Returns the Fields data for the Schema
     * @param Field $field
     * @return array<string,string>
     */
    private static function getField(Field $field): array {
        $type        = FieldType::getCodeType($field->type, $field->enumClass, false);
        $isDate      = $field->type === FieldType::Date;
        $isEnum      = $field->type === FieldType::Enum;

        $canAssign   = !$field->isID && !$field->isParent && !$isDate && !$isEnum;
        $assignDoc   = $canAssign ? "Assign|" : "";

        $default     = FieldType::getDefault($type);
        $nullDefault = $default === "null";
        $nullDoc     = $nullDefault ? "|null" : "";
        $nullPrefix  = $nullDefault ? "?" : "";

        $param       = "\${$field->name}";
        $dateParam   = "{$param}->toTime()";
        $enumParam   = "{$param}->name";
        $value       = $param;
        $valueNull   = $param;

        if ($isEnum) {
            $value     = "{$param}->name";
            $valueNull = "$param !== null ? $value : null";
        } elseif ($type === "bool") {
            $value     = "$param === true ? 1 : 0";
            $valueNull = $value;
        }

        return [
            "fieldKey"        => $field->dbName,
            "fieldName"       => $field->name,
            "fieldText"       => Strings::upperCaseFirst($field->name),
            "fieldDoc"        => "$type $param",
            "fieldArg"        => "$type $param",
            "fieldDocNull"    => "$type|null $param",
            "fieldArgNull"    => "?$type $param",
            "fieldDocDefault" => "$type$nullDoc $param",
            "fieldArgDefault" => "$nullPrefix$type $param = $default",
            "fieldDocEdit"    => "$assignDoc$type|null $param",
            "fieldArgEdit"    => ($canAssign ? "Assign|$type|null" : "?$type") . " $param = null",
            "fieldArgCreate"  => "?$type $param = null",
            "fieldParam"      => $param,
            "fieldValue"      => $value,
            "fieldValueNull"  => $valueNull,
            "fieldAssign"     => $isDate ? $dateParam : ($isEnum ? $enumParam : $param),
        ];
    }

    /**
     * Summary of joinFields
     * @param array{}[] $fields
     * @param string    $key
     * @param string    $prefix Optional.
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
     * @return array{}[]
     */
    private static function getValidations(SchemaModel $schemaModel): array {
        $hasFromDate = false;
        $result      = [];

        foreach ($schemaModel->validates as $validate) {
            $validation = [];
            switch ($validate->type) {
            case ValidateType::String:
            case ValidateType::Enum:
                $validation = [
                    "isString"    => true,
                    "isRequired"  => $validate->isRequired,
                    "isUnique"    => $validate->isUnique,
                    "typeOf"      => Strings::substringAfter($validate->typeOf, "\\"),
                    "method"      => $validate->method,
                    "emptySuffix" => $validate->isUnique || $validate->typeOf !== "" || $validate->maxLength > 0,
                    "maxLength"   => $validate->maxLength,
                    "fieldName"   => $validate->name,
                    "fieldError"  => $validate->getFieldError(),
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

            case ValidateType::Color:
                $validation = [
                    "isColor"    => true,
                    "isRequired" => $validate->isRequired,
                    "fieldName"  => $validate->name,
                    "fieldError" => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Number:
                $validation = [
                    "isNumber"      => true,
                    "isRequired"    => $validate->isRequired,
                    "isUnique"      => $validate->isUnique,
                    "emptySuffix"   => $validate->isUnique || $validate->isNumeric,
                    "typeOf"        => Strings::substringAfter($validate->typeOf, "\\"),
                    "typeError"     => $validate->getTypeError(),
                    "belongsTo"     => Strings::substringAfter($validate->belongsTo, "\\"),
                    "belongsError"  => $validate->getBelongsToError(),
                    "method"        => $validate->method,
                    "withParent"    => $validate->withParent,
                    "isNumeric"     => $validate->typeOf === "" && $validate->belongsTo === "",
                    "invalidPrefix" => $validate->isRequired,
                    "numericParams" => $validate->getNumericParams(),
                    "fieldName"     => $validate->name,
                    "fieldError"    => $validate->getFieldError(),
                ];
                break;

            case ValidateType::Date:
                $validation = [
                    "isDate"     => true,
                    "isRequired" => $validate->isRequired,
                    "dateName"   => $validate->dateInput,
                    "hourName"   => $validate->hourInput,
                    "errorText"  => Strings::startsWith($validate->name, "from") ? "FROM" : "TO",
                    "fieldError" => $validate->getFieldError(),
                ];
                if (Strings::startsWith($validate->name, "from")) {
                    $hasFromDate = true;
                } elseif (Strings::startsWith($validate->name, "to") && $hasFromDate) {
                    $validation += [
                        "hasPeriod"    => true,
                        "fromDateName" => "fromDate",
                        "fromHourName" => "fromHour",
                        "toDateName"   => "toDate",
                        "toHourName"   => "toHour",
                    ];
                    $hasFromDate = false;
                }
                break;

            case ValidateType::Price:
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

            default:
            }

            $result[] = $validation + [
                "hasIf"     => $validate->if !== "",
                "condition" => $validate->createCondition(),
                "pads"      => $validate->if !== "" ? "    " : "",
            ];
        }
        return $result;
    }

    /**
     * Returns true if there is a Color validation
     * @param SchemaModel $schemaModel
     * @return bool
     */
    private static function validatesColor(SchemaModel $schemaModel): bool {
        foreach ($schemaModel->validates as $validate) {
            if ($validate->type === ValidateType::Color) {
                return true;
            }
        }
        return false;
    }
}
