<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
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

        $idType      = FieldType::getCodeType($schemaModel->idType);
        $fields      = self::getAllFields($schemaModel);
        $uniques     = self::getSomeFields($schemaModel, isUnique: true);
        $parents     = self::getSomeFields($schemaModel, isParent: true);
        $subSchemas  = self::getSubTypes($schemaModel, forSchemas: true);
        $subTypes    = self::getSubTypes($schemaModel);
        $hasVirtual  = count($schemaModel->virtualFields) > 0;
        $hasParents  = count($parents) > 0;
        $hasDate     = count(self::getSomeFields($schemaModel, isDate: true)) > 0;
        $editParents = $schemaModel->hasPositions ? $parents : [];
        $queryName   = "{$schemaModel->name}Query";

        $contents    = Builder::render("database/Schema", [
            "namespace"           => $schemaModel->namespace,
            "name"                => $schemaModel->name,
            "table"               => $schemaModel->tableName,
            "column"              => "{$schemaModel->name}Column",
            "entity"              => "{$schemaModel->name}Entity",
            "status"              => "{$schemaModel->name}Status",
            "query"               => $queryName,
            "hasID"               => $schemaModel->hasID,
            "idName"              => $schemaModel->idName,
            "idDbName"            => $schemaModel->idDbName,
            "idType"              => $idType,
            "idDocType"           => FieldType::getDocType($idType),
            "hasIntID"            => $schemaModel->hasID && $idType === "int",
            "idText"              => Strings::upperCaseFirst($schemaModel->idName),
            "editType"            => $schemaModel->hasID ? "$queryName|$idType" : $queryName,
            "editDocType"         => $schemaModel->hasID ? "$queryName|" . FieldType::getDocType($idType) : $queryName,
            "convertType"         => $schemaModel->hasID ? "Query|$idType" : "Query",
            "convertDocType"      => $schemaModel->hasID ? "Query|" . FieldType::getDocType($idType) : "Query",
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
            "subSchemas"          => $subSchemas,
            "subTypes"            => $subTypes,
            "processEntity"       => count($subTypes) > 0 || $hasVirtual || $schemaModel->hasStatus,
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
            "fieldsCreateList"    => self::joinFields($fields, "fieldArgCreate", $schemaModel->usesRequest ? ", " : ""),
            "fieldsEditList"      => self::joinFields($fields, "fieldArgEdit", ", "),
            "uniques"             => $uniques,
            "parents"             => $parents,
            "editParents"         => $editParents,
            "parentsList"         => self::joinFields($parents, "fieldParam"),
            "parentsArgList"      => self::joinFields($parents, "fieldArg"),
            "parentsNullList"     => self::joinFields($parents, "fieldArgNull", ", "),
            "parentsDefList"      => self::joinFields($parents, "fieldArgDefault", ", "),
            "parentsEditList"     => self::joinFields($editParents, "fieldArg", ", "),
            "hasParents"          => $hasParents,
            "hasEditParents"      => $schemaModel->hasPositions && $hasParents,
            "hasDate"             => $hasDate,
        ]);

        $contents = Builder::alignParams($contents);
        return Strings::replace($contents, "(, ", "(");
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param SchemaModel $schemaModel
     * @param boolean     $forSchemas  Optional.
     * @return array{name:string,type:string,namespace:string}[]
     */
    private static function getSubTypes(SchemaModel $schemaModel, bool $forSchemas = false): array {
        $models = [];
        $result = [];

        foreach ($schemaModel->subRequests as $subRequest) {
            $model = "{$subRequest->namespace}/{$subRequest->modelName}";
            if (Arrays::contains($models, $model)) {
                continue;
            }

            if ($forSchemas || $subRequest->type === "") {
                $models[] = $model;
                $result[] = [
                    "name"      => $subRequest->name,
                    "type"      => $subRequest->modelName,
                    "namespace" => $subRequest->namespace,
                ];
            }
        }
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
     * @param boolean     $isUnique    Optional.
     * @param boolean     $isParent    Optional.
     * @param boolean     $isDate      Optional.
     * @return array<string,string>[]
     */
    private static function getSomeFields(
        SchemaModel $schemaModel,
        bool $isUnique = false,
        bool $isParent = false,
        bool $isDate = false,
    ): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($isUnique && $field->isUnique) {
                $result[] = self::getField($field);
            } elseif ($isParent && $field->isParent) {
                $result[] = self::getField($field);
            } elseif ($isDate && $field->dateType !== DateType::None) {
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
        $type      = FieldType::getCodeType($field->type);
        $docType   = FieldType::getDocType($type);
        $default   = FieldType::getDefault($type);
        $canAssign = !$field->isID && !$field->isParent;
        $assignDoc = $canAssign ? "Assign|" : "";
        $param     = "\${$field->name}";

        return [
            "fieldKey"        => $field->dbName,
            "fieldName"       => $field->name,
            "fieldText"       => Strings::upperCaseFirst($field->name),
            "fieldDoc"        => "$docType $param",
            "fieldDocNull"    => "$docType|null $param",
            "fieldDocEdit"    => "$assignDoc$docType|null $param",
            "fieldParam"      => $param,
            "fieldParamQuery" => $type === "bool" ? "$param === true ? 1 : 0" : $param,
            "fieldArg"        => "$type $param",
            "fieldArgNull"    => "?$type $param",
            "fieldArgDefault" => "$type $param = $default",
            "fieldArgCreate"  => "?$type $param = null",
            "fieldArgEdit"    => ($canAssign ? "Assign|$type|null" : "?$type") . " $param = null",
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
}
