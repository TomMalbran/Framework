<?php
namespace {{namespace}};

{{#subTypes}}use {{appNamespace}}Schema\{{folder}}\{{type}}Entity;
{{/subTypes}}
use {{namespace}}\{{entity}};
use {{namespace}}\{{column}};
use {{namespace}}\{{query}};

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

    protected const Schema = "{{name}}";


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
    private static function constructEntities(array $list): array {
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
     * @return {{query}}
     */
    protected static function createListQuery(Request $request): {{query}} {
        return new {{query}}();
    }

{{/hasFilters}}
{{#hasParents}}
    /**
     * Returns the Parents Query{{#parents}}
     * @param {{fieldDocNull}}{{/parents}}
     * @return {{query}}
     */
    protected static function createParentQuery({{parentsNullList}}): {{query}} {
        $query = new {{query}}();
        {{#parents}}
        $query->query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}}, {{fieldParam}} !== null);
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
        return self::getSchemaTotal($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}}) > 0;
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
        return self::getSchemaTotal($query) > 0;
    }

{{/uniques}}
    /**
     * Returns true if there is an {{name}} Entity with the given Query
     * @param {{query}} $query{{#hasDeleted}}
     * @param boolean $withDeleted Optional.{{/hasDeleted}}
     * @return boolean
     */
    public static function entityExists({{query}} $query{{#hasDeleted}}, bool $withDeleted = true{{/hasDeleted}}): bool {
        return self::getSchemaTotal($query->query{{#hasDeleted}}, $withDeleted{{/hasDeleted}}) > 0;
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
        $data = self::getSchemaEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
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
        $data = self::getSchemaEntity($query);
        return self::constructEntity($data);
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
        $data = self::getSchemaEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

{{/uniques}}
    /**
     * Returns the {{name}} Entity with the given Query
     * @param {{query}} $query{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}
     */
    protected static function getEntity({{query}} $query{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{entity}} {
        $data = self::getSchemaEntity($query->query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param {{query}} $query
     * @param {{column}} $column
     * @return string|integer
     */
    protected static function getEntityValue({{query}} $query, {{column}} $column): string|int {
        return self::getSchemaValue($query->query, $column->base());
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param {{query}} $query
     * @param {{column}} $column
     * @return array<string|integer>
     */
    protected static function getEntityColumn({{query}} $query, {{column}} $column): array {
        return self::getSchemaColumn($query->query, $column->value, $column->key());
    }



{{#hasID}}
    /**
     * Returns the {{idText}} for the given query
     * @param {{query}} $query
     * @return {{idDocType}}
     */
    public static function get{{idText}}({{query}} $query): {{idType}} {
        $result = self::getSchemaValue($query->query, "{{idKey}}");
        {{#hasIntID}}
        return Numbers::toInt($result);
        {{/hasIntID}}
        {{^hasIntID}}
        return (string)$result;
        {{/hasIntID}}
    }

    /**
     * Returns an array with all the {{idText}}s
     * @param {{query}}|null $query Optional.
     * @return {{idDocType}}[]
     */
    public static function get{{idText}}s(?{{query}} $query = null): array {
        $query  = $query !== null ? $query->query : null;
        $result = self::getSchemaColumn($query, "{{idKey}}", "{{idName}}");
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
        $query = static::createListQuery($request)->query;
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}})->query;
        {{/hasParents}}
        {{/hasFilters}}
        $list = self::getSchemaEntities({{#hasMainQuery}}$query, {{/hasMainQuery}}sort: $request);
        return self::constructEntities($list);
    }

    /**
     * Returns an array of {{name}} Entities
     * @param {{query}}|null $query Optional.
     * @param Request|null $sort Optional.
     * @param array<string,string> $selects Optional.
     * @param string[] $joins Optional.{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{entity}}[]
     */
    protected static function getEntityList(?{{query}} $query = null, ?Request $sort = null, array $selects = [], array $joins = []{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): array {
        $query = $query !== null ? $query->query : null;
        $list  = self::getSchemaEntities($query, $sort, $selects, $joins{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
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
        $query = static::createListQuery($request)->query;
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParamQuery}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}})->query;
        {{/hasParents}}
        {{/hasFilters}}
        return self::getSchemaTotal({{#hasMainQuery}}$query{{/hasMainQuery}});
    }

    /**
     * Returns a Total number of {{name}} Entities
     * @param {{query}}|null $query Optional.
     * @return integer
     */
    public static function getEntityTotal(?{{query}} $query = null): int {
        $query = $query !== null ? $query->query : null;
        return self::getSchemaTotal($query);
    }



{{#hasSelect}}
    /**
     * Returns a Select of {{name}} Entities{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return Select[]
     */
    public static function getSelect({{{parentsDefList}}}): array {
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}})->query;
        {{/hasParents}}
        return self::getSchemaSelect({{#hasParents}}$query{{/hasParents}});
    }

{{/hasSelect}}
    /**
     * Returns a Select of {{name}} Entities
     * @param {{query}} $query
     * @param {{column}}|null $idColumn Optional.
     * @param {{column}}[]|{{column}}|null $nameColumn Optional.
     * @param {{column}}[]|{{column}}|null $extraColumn Optional.
     * @param {{column}}|null $distinctColumn Optional.
     * @param boolean $useEmpty Optional.
     * @return Select[]
     */
    protected static function getEntitySelect(
        {{query}} $query,
        ?{{column}} $idColumn = null,
        array|{{column}}|null $nameColumn = null,
        array|{{column}}|null $extraColumn = null,
        ?{{column}} $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        $idKey       = $idColumn       !== null ? $idColumn->key()       : null;
        $distinctKey = $distinctColumn !== null ? $distinctColumn->value : null;
        $nameKey     = {{column}}::toKeys($nameColumn);
        $extraKey    = {{column}}::toKeys($extraColumn);
        return self::getSchemaSelect($query->query, $idKey, $nameKey, $extraKey, $distinctKey, $useEmpty);
    }

    /**
     * Returns the Search results for the {{name}} Entities
     * @param {{query}} $query
     * @param {{column}}|null $idColumn Optional.
     * @param {{column}}[]|{{column}}|null $nameColumn Optional.
     * @param integer $limit Optional.
     * @return Search[]
     */
    public static function getEntitySearch(
        {{query}} $query,
        ?{{column}} $idColumn = null,
        array|{{column}}|null $nameColumn = null,
        int $limit = 0,
    ): array {
        $idKey   = $idColumn !== null ? $idColumn->key() : null;
        $nameKey = {{column}}::toKeys($nameColumn);
        return self::getSchemaSearch($query->query, $idKey, $nameKey, $limit);
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param {{query}} $query
     * @param string $expression
     * @return array<string,string|integer>[]
     */
    protected static function getEntityData({{query}} $query, string $expression): array {
        return self::getSchemaData($query->query, $expression);
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param {{query}} $query
     * @param string $expression
     * @return array<string,string|integer>
     */
    protected static function getEntityRow({{query}} $query, string $expression): array {
        return self::getSchemaRow($query->query, $expression);
    }


{{#canCreate}}

    /**
     * Creates a new {{name}} Entity
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDocNull}} Optional.{{/fields}}{{#hasStatus}}
     * @param Status|null $status Optional.{{/hasStatus}}{{#hasTimestamps}}
     * @param integer $createdTime Optional.{{/hasTimestamps}}{{#hasUsers}}
     * @param integer $createdUser Optional.{{/hasUsers}}{{#hasPositions}}
     * @param boolean $skipOrder Optional.{{/hasPositions}}
     * @return integer
     */
    protected static function createEntity(?Request $entityRequest = null{{{fieldsCreateList}}}{{#hasStatus}}, ?Status $status = null{{/hasStatus}}{{#hasTimestamps}}, int $createdTime = 0{{/hasTimestamps}}{{#hasUsers}}, int $createdUser = 0{{/hasUsers}}{{#hasPositions}}, bool $skipOrder = false{{/hasPositions}}): int {
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
        if ($skipOrder) {
            return self::createSchemaEntity($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
        }
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::createSchemaEntityWithOrder($entityRequest, $entityFields{{#hasEditParents}}, orderQuery: $orderQuery->query{{/hasEditParents}}{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::createSchemaEntity($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
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

        $result = self::replaceSchemaEntity($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
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
     * @param boolean|null $isDeleted Optional.{{/canDelete}}{{#hasPositions}}
     * @param boolean $skipOrder Optional.{{/hasPositions}}
     * @param boolean $skipEmpty Optional.
     * @param boolean $skipUnset Optional.
     * @return boolean
     */
    protected static function editEntity({{editType}} $query, ?Request $entityRequest = null{{{fieldsEditList}}}{{#hasStatus}}, ?Status $status = null{{/hasStatus}}{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}{{#canDelete}}, ?bool $isDeleted = null{{/canDelete}}{{#hasPositions}}, bool $skipOrder = false{{/hasPositions}}, bool $skipEmpty = false, bool $skipUnset = false): bool {
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
        if ($skipOrder) {
            return self::editSchemaEntity(self::toQuery($query), $entityRequest, $entityFields{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}}, skipEmpty: $skipEmpty, skipUnset: $skipUnset);
        }
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::editSchemaEntityWithOrder(self::toQuery($query), $entityRequest, $entityFields{{#hasEditParents}}, orderQuery: $orderQuery->query{{/hasEditParents}}{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}}, skipEmpty: $skipEmpty, skipUnset: $skipUnset);
        {{/hasPositions}}
        {{^hasPositions}}
        {{/hasPositions}}
        {{^hasPositions}}
        return self::editSchemaEntity(self::toQuery($query), $entityRequest, $entityFields{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}}, skipEmpty: $skipEmpty, skipUnset: $skipUnset);
        {{/hasPositions}}
    }

    /**
     * Edits a value in a {{name}} Entity
     * @param {{editDocType}} $query
     * @param {{column}} $column
     * @param string|integer $value{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function editEntityValue({{editType}} $query, {{column}} $column, string|int $value{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        return self::editSchemaEntity(self::toQuery($query), fields: [
            $column->base() => $value,
        ]{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
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
        return self::editSchemaEntity(self::toQuery($query), fields: [
            $column->base() => Assign::increase($amount),
        ]{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
    }
{{/canEdit}}
{{#canDelete}}

    /**
     * Deletes the {{name}} Entity
     * @param {{editDocType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}{{#hasPositions}}
     * @param boolean $skipOrder Optional.{{/hasPositions}}
     * @return boolean
     */
    protected static function deleteEntity({{editType}} $query{{parentsEditList}}{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}{{#hasPositions}}, bool $skipOrder = false{{/hasPositions}}): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        {{#hasPositions}}
        if ($skipOrder) {
            return self::deleteSchemaEntity(self::toQuery($query){{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        }
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::deleteSchemaEntityWithOrder(self::toQuery($query){{#hasEditParents}}, orderQuery: $orderQuery->query{{/hasEditParents}}{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::deleteSchemaEntity(self::toQuery($query){{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
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
        return self::removeSchemaEntityWithOrder(self::toQuery($query){{#hasEditParents}}, $orderQuery->query{{/hasEditParents}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::removeSchemaEntity(self::toQuery($query));
        {{/hasPositions}}
    }
{{#hasPositions}}

    /**
     * Removes the {{name}} Entity
     * @param {{editDocType}} $query
     * @return boolean
     */
    protected static function removeAllEntities({{editType}} $query): bool {
        return self::removeSchemaEntity(self::toQuery($query));
    }
{{/hasPositions}}
{{/canRemove}}
{{#hasPositions}}

    /**
     * Ensures that the order of the Elements is correct
     * @param {{entity}}|null $entity
     * @param Request|array{}|null $fields{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return integer
     */
    protected static function ensureEntityOrder(?{{entity}} $entity, Request|array|null $fields{{parentsEditList}}): int {
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::ensureSchemaOrder($entity, $fields{{#hasEditParents}}, $orderQuery->query{{/hasEditParents}});
    }
{{/hasPositions}}

    /**
     * Ensures that only one Entity has the Unique column set
     * @param {{column}} $column
     * @param integer $id
     * @param integer $oldValue
     * @param integer $newValue
     * @param {{query}}|null $query Optional.
     * @return boolean
     */
    protected static function ensureUniqueData({{column}} $column, int $id, int $oldValue, int $newValue, ?{{query}} $query = null): bool {
        $query = $query !== null ? $query->query : null;
        return self::ensureSchemaUniqueData($column->base(), $id, $oldValue, $newValue, $query);
    }
{{#canConvert}}

    /**
     * Converts the {{query}} to a Query
     * @param {{editDocType}} $query
     * @return {{convertDocType}}
     */
    private static function toQuery({{editType}} $query): {{convertType}} {
        {{#hasID}}
        return $query instanceof {{query}} ? $query->query : $query;
        {{/hasID}}
        {{^hasID}}
        return $query->query;
        {{/hasID}}
    }
{{/canConvert}}
}
