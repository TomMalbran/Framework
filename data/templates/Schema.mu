<?php
namespace {{namespace}}Schema;

{{#hasStatus}}use {{namespace}}System\Status;
{{/hasStatus}}
use {{namespace}}Schema\{{name}}Entity;{{#subTypes}}
use {{namespace}}Schema\{{type}}Entity;{{/subTypes}}

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Utils\Search;{{#hasSelect}}
use Framework\Utils\Select;{{/hasSelect}}

/**
 * The {{name}} Schema
 */
class {{name}}Schema {

    /**
     * Loads the {{name}} Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("{{schemaName}}");
    }

    /**
     * Constructs the {{name}} Entity
     * @param array{} $data
     * @return {{name}}Entity
     */
    protected static function constructEntity(array $data): {{name}}Entity {
        $entity = new {{name}}Entity($data);
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
     * @param {{fieldDoc}}{{/parents}}
     * @return Query
     */
    protected static function createParentQuery({{parentsArgList}}): Query {
        $query = new Query();
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        return $query;
    }

{{/hasParents}}
{{#hasProcessed}}
    /**
     * Post Process the Result
     * @param {{name}}Entity $entity
     * @return {{name}}Entity
     */
    protected static function postProcess({{name}}Entity $entity): {{name}}Entity {
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
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        return self::schema()->exists($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}});
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
        $query = Query::create("{{fieldKey}}", "=", {{fieldParam}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        $query->addIf("{{idKey}}", "<>", $skipID);
        return self::schema()->exists($query);
    }

{{/uniques}}
    /**
     * Returns true if there is an {{name}} Entity with the given Query
     * @param Query $query{{#hasDeleted}}
     * @param boolean $withDeleted Optional.{{/hasDeleted}}
     * @return boolean
     */
    public static function entityExists(Query $query{{#hasDeleted}}, bool $withDeleted = true{{/hasDeleted}}): bool {
        return self::schema()->exists($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}});
    }



{{#hasID}}
    /**
     * Returns the {{name}} Entity with the given ID
     * @param {{idDocType}} ${{idName}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{name}}Entity
     */
    public static function getByID({{idType}} ${{idName}}{{{parentsDefList}}}{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{name}}Entity {
        $query = Query::create("{{idKey}}", "=", ${{idName}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        return self::getEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
    }

{{/hasID}}
{{#hasName}}
    /**
     * Returns the {{name}} Entity the given Name
     * @param string ${{nameKey}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return {{name}}Entity
     */
    public static function getByName(string ${{nameKey}}{{{parentsDefList}}}): {{name}}Entity {
        $query = Query::create("{{nameKey}}", "=", ${{nameKey}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
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
     * @return {{name}}Entity
     */
    public static function getBy{{fieldText}}({{fieldArg}}{{{parentsDefList}}}{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{name}}Entity {
        $query = Query::create("{{fieldKey}}", "=", {{fieldParam}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        return self::getEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
    }

{{/uniques}}
    /**
     * Returns the {{name}} Entity with the given Query
     * @param Query $query{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{name}}Entity
     */
    protected static function getEntity(Query $query{{#canDelete}}, bool $withDeleted = true{{/canDelete}}{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): {{name}}Entity {
        $data = self::schema()->getRow($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

    /**
     * Returns a value of the {{name}} Entity with the given Query
     * @param Query $query
     * @param string $column
     * @return mixed
     */
    protected static function getEntityValue(Query $query, string $column): mixed {
        return self::schema()->getValue($query, $column);
    }



{{#hasID}}
    /**
     * Returns an array with all the {{idText}}s
     * @param Query|null $query Optional.
     * @return int[]
     */
    public static function get{{idText}}s(?Query $query = null): array {
        return self::schema()->getColumn($query, "{{idKey}}");
    }

{{/hasID}}
    /**
     * Returns a list of {{name}} Entities{{#hasFilters}}
     * @param Request $request{{/hasFilters}}{{^hasFilters}}
     * @param Request|null $request Optional.{{/hasFilters}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return {{name}}Entity[]
     */
    public static function getList({{#hasFilters}}Request $request{{/hasFilters}}{{^hasFilters}}?Request $request = null{{/hasFilters}}{{{parentsDefList}}}): array {
        {{#hasFilters}}
        $query = static::createListQuery($request);
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}});
        {{/hasParents}}
        {{/hasFilters}}
        $result = self::getEntityList({{#hasMainQuery}}$query, {{/hasMainQuery}}sort: $request);
        return $result;
    }

    /**
     * Returns an array of {{name}} Entities
     * @param Query|null $query Optional.
     * @param Request|null $sort Optional.{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{name}}Entity[]
     */
    protected static function getEntityList(?Query $query = null, ?Request $sort = null{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): array {
        $list   = self::schema()->getAll($query, $sort{{#hasEncrypt}}, $decrypted{{/hasEncrypt}});
        $result = [];
        foreach ($list as $data) {
            $result[] = self::constructEntity($data);
        }
        return $result;
    }

    /**
     * Gets a Total number of {{name}} Entities{{#hasFilters}}
     * @param Request $request{{/hasFilters}}{{#parents}}
     * @param {{fieldDoc}} Optional.{{/parents}}
     * @return integer
     */
    public static function getTotal({{#hasFilters}}Request $request{{/hasFilters}}{{{parentsDefList}}}): int {
        {{#hasFilters}}
        $query = static::createListQuery($request);
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        {{/hasFilters}}
        {{^hasFilters}}
        {{#hasParents}}
        $query = self::createParentQuery({{parentsList}});
        {{/hasParents}}
        {{/hasFilters}}
        return self::getEntityTotal({{#hasMainQuery}}$query{{/hasMainQuery}});
    }

    /**
     * Gets a Total number of {{name}} Entities
     * @param Query|null $query Optional.{{#canDelete}}
     * @param boolean $withDeleted Optional.{{/canDelete}}
     * @return integer
     */
    protected static function getEntityTotal(?Query $query = null{{#canDelete}}, bool $withDeleted = true{{/canDelete}}): int {
        return self::schema()->getTotal($query{{#canDelete}}, $withDeleted{{/canDelete}});
    }

    /**
     * Returns the Search results of {{name}} Entities
     * @param Query $query
     * @param string[]|string|null $name Optional.
     * @param string|null $idName Optional.
     * @param integer $limit Optional.
     * @return Search[]
     */
    public static function getSearch(Query $query, array|string $name = null, ?string $idName = null, int $limit = 0): array {
        return self::schema()->getSearch($query, $name, $idName, $limit);
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
        return self::schema()->getSelect({{#hasParents}}$query{{/hasParents}});
    }

{{/hasSelect}}

{{#canCreate}}

    /**
     * Creates a new {{name}} Entity
     * @param Request|array{} $fields{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}
     * @param array{}|null $extras Optional.{{#hasUsers}}
     * @param integer $credentialID Optional.{{/hasUsers}}
     * @return integer
     */
    protected static function createEntity(Request|array $fields{{parentsEditList}}, ?array $extras = null{{#hasUsers}}, int $credentialID = 0{{/hasUsers}}): int {
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::schema()->createWithOrder($fields, $extras{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $credentialID{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::schema()->create($fields, $extras{{#hasUsers}}, $credentialID{{/hasUsers}});
        {{/hasPositions}}
    }
{{/canCreate}}
{{#canEdit}}

    /**
     * Edita the given {{name}} Entity
     * @param {{editDocType}} $query
     * @param Request|array{} $fields{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}
     * @param array{}|null $extras Optional.{{#hasUsers}}
     * @param integer $credentialID Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function editEntity({{editType}} $query, Request|array $fields{{parentsEditList}}, ?array $extras = null{{#hasUsers}}, int $credentialID = 0{{/hasUsers}}): bool {
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::schema()->editWithOrder($query, $fields, $extras{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $credentialID{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::schema()->edit($query, $fields, $extras{{#hasUsers}}, $credentialID{{/hasUsers}});
        {{/hasPositions}}
    }
{{/canEdit}}
{{#canReplace}}

    /**
     * Replaces the {{name}} Entity
     * @param Request|array{} $fields
     * @param array{}|null $extras Optional.{{#hasUsers}}
     * @param integer $credentialID Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function replaceEntity(Request|array $fields, ?array $extras = null{{#hasUsers}}, int $credentialID = 0{{/hasUsers}}): bool {
        return self::schema()->replace($fields, $extras{{#hasUsers}}, $credentialID{{/hasUsers}});
    }
{{/canReplace}}
{{#canBatch}}

    /**
     * Batches the {{name}} Entities
     * @param array{}[] $fields
     * @return boolean
     */
    protected static function batchEntities(array $fields): bool {
        if (empty($fields)) {
            return false;
        }
        return self::schema()->batch($fields);
    }
{{/canBatch}}
{{#canDelete}}

    /**
     * Deletes the {{name}} Entity
     * @param {{editDocType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}{{#hasUsers}}
     * @param integer $credentialID Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function deleteEntity({{editType}} $query{{parentsEditList}}{{#hasUsers}}, int $credentialID = 0{{/hasUsers}}): bool {
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::schema()->deleteWithOrder($query{{#hasEditParents}}, orderQuery: $orderQuery{{/hasEditParents}}{{#hasUsers}}, credentialID: $credentialID{{/hasUsers}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::schema()->delete($query{{#hasUsers}}, $credentialID{{/hasUsers}});
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
        return self::schema()->removeWithOrder($query{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::schema()->remove($query);
        {{/hasPositions}}
    }
{{/canRemove}}
{{#hasPositions}}

    /**
     * Ensures that the order of the Elements is correct
     * @param {{name}}Entity|null $entity
     * @param Request|array{}|null $fields{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return boolean
     */
    protected static function ensurePosOrder(?{{name}}Entity $entity, Request|array|null $fields{{parentsEditList}}): bool {
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::schema()->ensurePosOrder($entity, $fields{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
    }
{{/hasPositions}}
}
