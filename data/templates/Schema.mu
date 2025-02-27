<?php
namespace {{namespace}}Schema;

{{#subTypes}}use {{appNamespace}}Schema\{{type}}Entity;
{{/subTypes}}
use {{namespace}}Schema\{{entity}};
use {{namespace}}Schema\{{column}};

use Framework\Request;{{#hasUsers}}
use Framework\Auth\Auth;{{/hasUsers}}
use Framework\Database\Schema;
use Framework\Database\Query;{{#canEdit}}
use Framework\Database\Assign;{{/canEdit}}{{#hasStatus}}
use Framework\System\Status;{{/hasStatus}}
use Framework\Utils\Arrays;
use Framework\Utils\Search;
use Framework\Utils\Select;{{#hasIntID}}
use Framework\Utils\Numbers;{{/hasIntID}}

/**
 * The {{name}} Schema
 */
class {{name}}Schema extends Schema {

    const Schema = "{{name}}";


    /**
     * Constructs the {{name}} Entity
     * @param array{} $data
     * @return {{entity}}
     */
    protected static function constructEntity(array $data): {{entity}} {
        $entity = new {{entity}}($data);
        {{#processEntity}}
        if (!$entity->isEmpty()) {
            {{#subTypes}}
            foreach ($entity->{{name}} as $index => $subEntity) {
                $entity->{{name}}[$index] = new {{type}}Entity($subEntity);
            }
            {{/subTypes}}
            {{#hasStatus}}
            $entity->statusName  = Status::getName($entity->status);
            $entity->statusColor = Status::getColor($entity->status);
            {{/hasStatus}}
            {{#hasProcessed}}
            $entity = static::postProcess($entity);
            {{/hasProcessed}}
        }
        {{/processEntity}}
        return $entity;
    }

    /**
     * Constructs a list of {{name}} Entities
     * @param array{}[] $list
     * @return {{entity}}[]
     */
    protected static function constructEntities(array $list): array {
        $result = [];
        foreach ($list as $data) {
            $result[] = self::constructEntity($data);
        }
        return $result;
    }

{{#hasFilters}}
    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        return new Query();
    }

{{/hasFilters}}
{{#hasParents}}
    /**
     * Returns the Parents Query{{#parents}}
     * @param {{fieldDocNull}}{{/parents}}
     * @return Query
     */
    protected static function createParentQuery({{parentsNullList}}): Query {
        $query = new Query();
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        return $query;
    }

{{/hasParents}}
{{#hasProcessed}}
    /**
     * Post Process the Result
     * @param {{entity}} $entity
     * @return {{entity}}
     */
    protected static function postProcess({{entity}} $entity): {{entity}} {
        return $entity;
    }

{{/hasProcessed}}


{{#hasID}}
    /**
     * Returns true if there is an {{name}} Entity with the given {{idText}}
     * @param {{idDocType}} ${{idName}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}{{#hasDeleted}}
     * @param boolean $withDeleted Optional.{{/hasDeleted}}
     * @return boolean
     */
    public static function exists({{idType}} ${{idName}}{{{parentsDefList}}}{{#hasDeleted}}, bool $withDeleted = true{{/hasDeleted}}): bool {
        $query = Query::create("{{idKey}}", "=", ${{idName}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        return self::entityExists($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}});
    }

{{/hasID}}
{{#uniques}}
    /**
     * Returns true if there is a {{name}} Entity with the given {{fieldText}}
     * @param {{fieldDoc}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function {{fieldName}}Exists({{fieldArg}}{{{parentsDefList}}}, int $skipID = 0): bool {
        $query = Query::create("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        $query->addIf("{{idKey}}", "<>", $skipID);
        return self::entityExists($query);
    }

{{/uniques}}
    /**
     * Returns true if there is an {{name}} Entity with the given Query
     * @param Query $query{{#hasDeleted}}
     * @param boolean $withDeleted Optional.{{/hasDeleted}}
     * @return boolean
     */
    public static function entityExists(Query $query{{#hasDeleted}}, bool $withDeleted = true{{/hasDeleted}}): bool {
        return self::getEntityTotal($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}}) > 0;
    }



{{#hasID}}
    /**
     * Returns the {{name}} Entity with the given ID
     * @param {{idDocType}} ${{idName}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}
     */
    public static function getByID({{idType}} ${{idName}}{{{parentsDefList}}}{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{entity}} {
        $query = Query::create("{{idKey}}", "=", ${{idName}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        return self::getEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
    }

{{/hasID}}
{{#hasName}}
    /**
     * Returns the {{name}} Entity the given Name
     * @param string ${{nameKey}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return {{entity}}
     */
    public static function getByName(string ${{nameKey}}{{{parentsDefList}}}): {{entity}} {
        $query = Query::create("{{nameKey}}", "=", ${{nameKey}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        return self::getEntity($query);
    }

{{/hasName}}
{{#uniques}}
    /**
     * Returns the {{name}} Entity with the given {{fieldText}}
     * @param {{fieldDoc}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}
     */
    public static function getBy{{fieldText}}({{fieldArg}}{{{parentsDefList}}}{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{entity}} {
        $query = Query::create("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        return self::getEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
    }

{{/uniques}}
    /**
     * Returns the {{name}} Entity with the given Query
     * @param Query $query{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}
     */
    protected static function getEntity(Query $query{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{entity}} {
        $data = self::getEntityData($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query $query
     * @param {{column}} $column
     * @return string|integer|float|boolean
     */
    protected static function getEntityValue(Query $query, {{column}} $column): string|int|float|bool {
        return self::getValueData($query, $column->base());
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query $query
     * @param {{column}} $column
     * @return array<string|integer>
     */
    protected static function getEntityColumn(Query $query, {{column}} $column): array {
        return self::getColumnData($query, $column->value, $column->key());
    }



{{#hasID}}
    /**
     * Returns the {{idText}} for the given query
     * @param Query $query
     * @return {{idDocType}}
     */
    public static function get{{idText}}(Query $query): {{idType}} {
        $result = self::getValueData($query, "{{idKey}}");
        {{#hasIntID}}
        return Numbers::toInt($result);
        {{/hasIntID}}
        {{^hasIntID}}
        return (string)$result;
        {{/hasIntID}}
    }

    /**
     * Returns an array with all the {{idText}}s
     * @param Query|null $query Optional.
     * @return {{idDocType}}[]
     */
    public static function get{{idText}}s(?Query $query = null): array {
        $result = self::getColumnData($query, "{{idKey}}");
        {{#hasIntID}}
        return Arrays::toInts($result);
        {{/hasIntID}}
        {{^hasIntID}}
        return Arrays::toStrings($result);
        {{/hasIntID}}
    }

{{/hasID}}
    /**
     * Returns a list of {{name}} Entities{{#hasFilters}}
     * @param Request $request{{/hasFilters}}{{^hasFilters}}
     * @param Request|null $request Optional.{{/hasFilters}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return {{entity}}[]
     */
    public static function getList({{#hasFilters}}Request $request{{/hasFilters}}{{^hasFilters}}?Request $request = null{{/hasFilters}}{{{parentsDefList}}}): array {
        {{#hasFilters}}
        $query = static::createListQuery($request);
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}});
        {{/hasParents}}
        {{/hasFilters}}
        return self::getEntityList({{#hasMainQuery}}$query, {{/hasMainQuery}}sort: $request);
    }

    /**
     * Returns an array of {{name}} Entities
     * @param Query|null $query Optional.
     * @param Request|null $sort Optional.{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}[]
     */
    protected static function getEntityList(?Query $query = null, ?Request $sort = null{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): array {
        $list = self::getEntitiesData($query, $sort{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntities($list);
    }

    /**
     * Returns a Total number of {{name}} Entities{{#hasFilters}}
     * @param Request $request{{/hasFilters}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return integer
     */
    public static function getTotal({{#hasFilters}}Request $request{{/hasFilters}}{{{parentsDefList}}}): int {
        {{#hasFilters}}
        $query = static::createListQuery($request);
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}});
        {{/hasParents}}
        {{/hasFilters}}
        return self::getEntityTotal({{#hasMainQuery}}$query{{/hasMainQuery}});
    }



{{#hasSelect}}
    /**
     * Returns a Select of {{name}} Entities{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return Select[]
     */
    public static function getSelect({{{parentsDefList}}}): array {
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}});
        {{/hasParents}}
        return self::getSelectData({{#hasParents}}$query{{/hasParents}});
    }

{{/hasSelect}}
    /**
     * Returns a Select of {{name}} Entities
     * @param Query|null $query Optional.
     * @param {{column}}|null $orderColumn Optional.
     * @param boolean $orderAsc Optional.
     * @param {{column}}|null $idColumn Optional.
     * @param {{column}}[]|{{column}}|null $nameColumn Optional.
     * @param {{column}}[]|{{column}}|null $extraColumn Optional.
     * @param {{column}}|null $distinctColumn Optional.
     * @param boolean $useEmpty Optional.
     * @return Select[]
     */
    protected static function getEntitySelect(
        ?Query $query = null,
        ?{{column}} $orderColumn = null,
        bool $orderAsc = true,
        ?{{column}} $idColumn = null,
        array|{{column}}|null $nameColumn = null,
        array|{{column}}|null $extraColumn = null,
        ?{{column}} $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        $orderKey    = !empty($orderColumn)    ? $orderColumn->key()    : null;
        $idKey       = !empty($idColumn)       ? $idColumn->key()       : null;
        $distinctKey = !empty($distinctColumn) ? $distinctColumn->value : null;

        $nameKey     = {{column}}::toKeys($nameColumn);
        $extraKey    = {{column}}::toKeys($extraColumn);

        return self::getSelectData($query, $orderKey, $orderAsc, $idKey, $nameKey, $extraKey, $distinctKey, $useEmpty);
    }

    /**
     * Returns the Search results for the {{name}} Entities
     * @param Query $query
     * @param {{column}}|null $idColumn Optional.
     * @param {{column}}[]|{{column}}|null $nameColumn Optional.
     * @param integer $limit Optional.
     * @return Search[]
     */
    public static function getEntitySearch(
        Query $query,
        ?{{column}} $idColumn = null,
        array|{{column}}|null $nameColumn = null,
        int $limit = 0,
    ): array {
        $idKey   = !empty($idColumn) ? $idColumn->key() : null;
        $nameKey = {{column}}::toKeys($nameColumn);
        return self::getSearchData($query, $idKey, $nameKey, $limit);
    }


{{#canCreate}}

    /**
     * Creates a new {{name}} Entity
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDocNull}} Optional.{{/fields}}{{#hasStatus}}
     * @param Status|null $status Optional.{{/hasStatus}}{{#hasTimestamps}}
     * @param integer $createdTime Optional.{{/hasTimestamps}}{{#hasUsers}}
     * @param integer $createdUser Optional.{{/hasUsers}}
     * @return integer
     */
    protected static function createEntity(?Request $entityRequest = null{{{fieldsCreateList}}}{{#hasStatus}}, ?Status $status = null{{/hasStatus}}{{#hasTimestamps}}, int $createdTime = 0{{/hasTimestamps}}{{#hasUsers}}, int $createdUser = 0{{/hasUsers}}): int {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $entityFields["status"] = $status->name;
        }
        {{/hasStatus}}
        {{#hasTimestamps}}
        if ($createdTime > 0) {
            $entityFields["createdTime"] = $createdTime;
        }
        {{/hasTimestamps}}
        {{#hasUsers}}
        if ($createdUser === 0) {
            $createdUser = Auth::getID();
        }
        {{/hasUsers}}

        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::createEntityWithOrder($entityRequest, $entityFields{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::createEntityData($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
        {{/hasPositions}}
    }
{{/canCreate}}
{{#canReplace}}

    /**
     * Replaces the {{name}} Entity
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDocNull}} Optional.{{/fields}}{{#hasStatus}}
     * @param Status|null $status Optional.{{/hasStatus}}{{#hasUsers}}
     * @param integer $createdUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function replaceEntity(?Request $entityRequest = null{{{fieldsCreateList}}}{{#hasStatus}}, ?Status $status = null{{/hasStatus}}{{#hasUsers}}, int $createdUser = 0{{/hasUsers}}): bool {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $entityFields["status"] = $status->name;
        }
        {{/hasStatus}}
        {{#hasUsers}}
        if ($createdUser === 0) {
            $createdUser = Auth::getID();
        }
        {{/hasUsers}}

        $result = self::replaceEntityData($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
        return $result > 0;
    }
{{/canReplace}}
{{#canEdit}}

    /**
     * Edits a {{name}} Entity
     * @param {{editDocType}} $query
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDocEdit}} Optional.{{/fields}}{{#hasStatus}}
     * @param Status|null $status Optional.{{/hasStatus}}{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}{{#canDelete}}
     * @param boolean|null $isDeleted Optional.{{/canDelete}}
     * @return boolean
     */
    protected static function editEntity({{editType}} $query, ?Request $entityRequest = null{{{fieldsEditList}}}{{#hasStatus}}, ?Status $status = null{{/hasStatus}}{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}{{#canDelete}}, ?bool $isDeleted = null{{/canDelete}}): bool {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $entityFields["status"] = $status->name;
        }
        {{/hasStatus}}
        {{#canDelete}}
        if ($isDeleted !== null) {
            $entityFields["isDeleted"] = $isDeleted;
        }
        {{/canDelete}}
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }
        {{/hasUsers}}

        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::editEntityWithOrder($query, $entityRequest, $entityFields{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::editEntityData($query, $entityRequest, $entityFields{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
    }

    /**
     * Increments a value in a {{name}} Entity
     * @param {{editDocType}} $query
     * @param {{column}} $column
     * @param integer $amount Optional.{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function increaseEntity({{editType}} $query, {{column}} $column, int $amount = 1{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        return self::editEntityData($query, fields: [
            $column->base() => Assign::increase($amount),
        ]{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
    }
{{/canEdit}}
{{#canDelete}}

    /**
     * Deletes the {{name}} Entity
     * @param {{editDocType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function deleteEntity({{editType}} $query{{parentsEditList}}{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::deleteEntityWithOrder($query{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::deleteEntityData($query{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
    }
{{/canDelete}}
{{#canRemove}}

    /**
     * Removes the {{name}} Entity
     * @param {{editDocType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}
     * @return boolean
     */
    protected static function removeEntity({{editType}} $query{{parentsEditList}}): bool {
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::removeEntityWithOrder($query{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::removeEntityData($query);
        {{/hasPositions}}
    }
{{/canRemove}}
{{#hasPositions}}

    /**
     * Ensures that the order of the Elements is correct
     * @param {{entity}}|null $entity
     * @param Request|array{}|null $fields{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return integer
     */
    protected static function ensurePosOrder(?{{entity}} $entity, Request|array|null $fields{{parentsEditList}}): int {
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::ensureEntityOrder($entity, $fields{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
    }
{{/hasPositions}}
}
