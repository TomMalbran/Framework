<?php
namespace {{namespace}}Schema;

{{#hasStatus}}use {{appNamespace}}System\Status;
{{/hasStatus}}
{{#subTypes}}use {{appNamespace}}Schema\{{type}}Entity;
{{/subTypes}}
use {{namespace}}Schema\{{name}}Entity;

use Framework\Request;{{#hasUsers}}
use Framework\Auth\Auth;{{/hasUsers}}
use Framework\Database\Schema;
use Framework\Database\Query;{{#canEdit}}
use Framework\Database\Assign;{{/canEdit}}{{#hasSelect}}
use Framework\Utils\Select;{{/hasSelect}}

/**
 * The {{name}} Schema
 */
class {{name}}Schema extends Schema {

    const Name = "{{name}}";


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

    /**
     * Constructs a list of {{name}} Entities
     * @param array{}[] $list
     * @return {{name}}Entity[]
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
        $query = Query::create("{{fieldKey}}", "=", {{fieldParam}});
        {{#parents}}
        $query->addIf("{{fieldKey}}", "=", {{fieldParam}});
        {{/parents}}
        $query->addIf("{{idKey}}", "<>", $skipID);
        return self::entityExists($query);
    }

{{/uniques}}


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
        $data = self::getEntityData($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }



{{#hasID}}
    /**
     * Returns an array with all the {{idText}}s
     * @param Query|null $query Optional.
     * @return int[]
     */
    public static function get{{idText}}s(?Query $query = null): array {
        return self::getEntityColumn($query, "{{idKey}}");
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
        return self::getEntityList({{#hasMainQuery}}$query, {{/hasMainQuery}}sort: $request);
    }

    /**
     * Returns an array of {{name}} Entities
     * @param Query|null $query Optional.
     * @param Request|null $sort Optional.{{#hasEncrypt}}
     * @param boolean $decrypted Optional.{{/hasEncrypt}}
     * @return {{name}}Entity[]
     */
    protected static function getEntityList(?Query $query = null, ?Request $sort = null{{#hasEncrypt}}, bool $decrypted = false{{/hasEncrypt}}): array {
        $list = self::getEntitiesData($query, $sort{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntities($list);
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
        return self::getEntitySelect({{#hasParents}}$query{{/hasParents}});
    }

{{/hasSelect}}

{{#canCreate}}

    /**
     * Creates a new {{name}} Entity
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDoc}} Optional.{{/fields}}{{#hasTimestamps}}
     * @param integer $createdTime Optional.{{/hasTimestamps}}{{#hasUsers}}
     * @param integer $createdUser Optional.{{/hasUsers}}
     * @return integer
     */
    protected static function createEntity(?Request $entityRequest = null{{{fieldsList}}}{{#hasTimestamps}}, int $createdTime = 0{{/hasTimestamps}}{{#hasUsers}}, int $createdUser = 0{{/hasUsers}}): int {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== {{{defaultValue}}}) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#hasTimestamps}}
        if (!empty($createdTime)) {
            $entityFields["createdTime"] = $createdTime;
        }
        {{/hasTimestamps}}
        {{#hasUsers}}
        if (!empty($createdUser)) {
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
     * @param {{fieldDoc}} Optional.{{/fields}}{{#hasUsers}}
     * @param integer $createdUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function replaceEntity(?Request $entityRequest = null{{{fieldsList}}}{{#hasUsers}}, int $createdUser = 0{{/hasUsers}}): bool {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== {{{defaultValue}}}) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#hasUsers}}
        if (!empty($createdUser)) {
            $createdUser = Auth::getID();
        }
        {{/hasUsers}}
        return self::replaceEntityData($entityRequest, $entityFields{{#hasUsers}}, credentialID: $createdUser{{/hasUsers}});
    }
{{/canReplace}}
{{#canEdit}}

    /**
     * Edits a {{name}} Entity
     * @param {{editDocType}} $query
     * @param Request|null $entityRequest Optional.{{#fields}}
     * @param {{fieldDocEdit}} Optional.{{/fields}}{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}{{#canDelete}}
     * @param boolean|null $isDeleted Optional.{{/canDelete}}
     * @return boolean
     */
    protected static function editEntity({{editType}} $query, ?Request $entityRequest = null{{{fieldsEditList}}}{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}{{#canDelete}}, ?bool $isDeleted = null{{/canDelete}}): bool {
        $entityFields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $entityFields["{{fieldKey}}"] = {{fieldParam}};
        }
        {{/fields}}
        {{#canDelete}}
        if ($isDeleted !== null) {
            $entityFields["isDeleted"] = $isDeleted;
        }
        {{/canDelete}}
        {{#hasUsers}}
        if (!empty($modifiedUser)) {
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
     * @param string $column
     * @param integer $amount Optional.{{#hasUsers}}
     * @param integer $modifiedUser Optional.{{/hasUsers}}
     * @return boolean
     */
    protected static function increaseEntity({{editType}} $query, string $column, int $amount = 1{{#hasUsers}}, int $modifiedUser = 0{{/hasUsers}}): bool {
        {{#hasUsers}}
        if (!empty($modifiedUser)) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        return self::editEntityData($query, null, [
            $column => Assign::increase($amount),
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
        if (!empty($modifiedUser)) {
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
     * @param {{name}}Entity|null $entity
     * @param Request|array{}|null $fields{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return boolean
     */
    protected static function ensurePosOrder(?{{name}}Entity $entity, Request|array|null $fields{{parentsEditList}}): bool {
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::ensureEntityOrder($entity, $fields{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
    }
{{/hasPositions}}
}
