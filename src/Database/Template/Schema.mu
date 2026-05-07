<?php
namespace {{namespace}};

{{#hasImports}}
{{#imports}}use {{.}};
{{/imports}}

{{/hasImports}}
use {{namespace}}\{{entityClass}};{{#hasRequest}}
use {{namespace}}\{{requestClass}};{{/hasRequest}}
use {{namespace}}\{{columnClass}};
use {{namespace}}\{{queryClass}};{{#hasStatus}}
use {{namespace}}\{{statusClass}};{{/hasStatus}}

use Framework\IO\Request;{{#hasValidation}}
use Framework\IO\Errors;{{/hasValidation}}
use Framework\IO\Search;
use Framework\IO\Select;{{#hasUsers}}
use Framework\Auth\Auth;{{/hasUsers}}
use Framework\Database\Schema;
use Framework\Database\SchemaModel;{{#hasQuery}}
use Framework\Database\Query\Query;{{/hasQuery}}{{#canEdit}}
use Framework\Database\Query\Assign;{{/canEdit}}{{#hasOperator}}
use Framework\Database\Query\Operator as QueryOperator;{{/hasOperator}}
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;{{#hasExpressions}}
use Framework\Database\Model\Expression;{{/hasExpressions}}{{#hasCounts}}
use Framework\Database\Model\Count;{{/hasCounts}}{{#hasRelations}}
use Framework\Database\Model\Relation;{{/hasRelations}}{{#hasSubRequests}}
use Framework\Database\Model\SubRequest;{{/hasSubRequests}}{{#hasValidation}}
use Framework\Database\Type\Result;{{/hasValidation}}{{#hasDate}}
use Framework\Date\Date;{{/hasDate}}{{#hasDateType}}
use Framework\Date\Type\DateType;{{/hasDateType}}{{#hasID}}
use Framework\Utils\Arrays;{{/hasID}}
use Framework\Utils\Dictionary;{{#usesNumbers}}
use Framework\Utils\Numbers;{{/usesNumbers}}{{#hasJsonType}}

use JsonSerializable;{{/hasJsonType}}

/**
 * The {{name}} Schema
 */
class {{name}}Schema extends Schema {

    protected static ?SchemaModel $model = null;

    protected static string $modelName = "{{name}}";
    protected static string $tableName = "{{tableName}}";
    protected static string $idName    = "{{idName}}";
    protected static string $idDbName  = "{{idDbName}}";
    protected static bool   $canDelete = {{canDeleteValue}};



    /**
     * Creates a new Schema Model instance
     * @return SchemaModel
     */
    #[\Override]
    public static function getModel(): SchemaModel {
        if (static::$model === null) {
            static::$model = new SchemaModel(
                name:          "{{name}}",
                hasUsers:      {{hasUsersValue}},
                hasTimestamps: {{hasTimestampsValue}},
                hasPositions:  {{hasPositionsValue}},
                hasStatus:     {{hasStatusValue}},
                canCreate:     {{canCreateValue}},
                canEdit:       {{canEditValue}},
                canDelete:     {{canDeleteValue}},
                mainFields:    [
                {{#mainFields}}
                    Field::create({{{params}}}),
                {{/mainFields}}
                ],
                {{#hasExpressions}}
                expressions:   [
                {{#expressions}}
                    Expression::create({{#paramList}}
                        {{{.}}},{{/paramList}}
                    ),
                {{/expressions}}
                ],
                {{/hasExpressions}}
                {{#hasCounts}}
                counts:        [
                {{#counts}}
                    Count::create({{{params}}}),
                {{/counts}}
                ],
                {{/hasCounts}}
                {{#hasRelations}}
                relations:     [
                {{#relations}}
                    Relation::create({{{params}}}, fields: [
                        {{#fields}}
                        Field::create({{{params}}}),
                        {{/fields}}
                    ]),
                {{/relations}}
                ],
                {{/hasRelations}}
            );
        }
        return static::$model;
    }

{{#hasSubRequests}}
    /**
     * Returns a list of SubRequests
     * @return list<SubRequest>
     */
    #[\Override]
    public static function getSubRequests(): array {
        return [
        {{#subRequests}}
            SubRequest::create({{{params}}}),
        {{/subRequests}}
        ];
    }

{{/hasSubRequests}}
{{#hasValidation}}


    /**
     * Returns true if the {{name}} Entity can be edited{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return bool
     */
    public static function canEdit({{parentsArgList}}): bool {
        return true;
    }

    /**
     * Validates the {{name}} Request
     * @param {{requestClass}} $request{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}
     * @return Result
     */
    public static function validateRequest(
        {{requestClass}} $request,{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}
    ): Result {
        {{#hasEnumID}}
        $id          = {{idEnumClass}}::fromRequest($request->getRequest(), "{{idName}}");
        {{/hasEnumID}}
        {{^hasEnumID}}
        $id          = $request->id;
        {{/hasEnumID}}
        $errors      = new Errors();
        $canValidate = false;

        if (!self::canEdit({{parentsList}})) {
            $errors->form = "{{errorPrefix}}EDIT";
        } elseif ($request->isEdit && !self::exists($id{{parentsSecList}})) {
            $errors->form = "{{errorPrefix}}EXISTS";
        } else {
{{#validations}}
    {{#hasIf}}
            {{{condition}}}
    {{/hasIf}}
    {{#isString}}
        {{#isRequired}}
            {{pads}}if (!$request->{{fieldName}}->isValid()) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}{{#emptySuffix}}_EMPTY{{/emptySuffix}}";
        {{/isRequired}}
        {{#typeOf}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if (!{{typeOf}}::{{method}}($request->{{fieldName}}->get())) {
            {{pads}}    $errors->{{fieldName}} = "{{typeInvError}}";
        {{/typeOf}}
        {{#isUnique}}
            {{pads}}} elseif (self::{{fieldName}}Exists($request->{{fieldName}}->get(){{parentsSecList}}, $id)) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}_EXISTS";
        {{/isUnique}}
        {{#maxLength}}
            {{pads}}} elseif (!$request->{{fieldName}}->isValidLength({{maxLength}})) {
            {{pads}}    $errors->add("{{fieldName}}", "{{fieldError}}_LENGTH", {{maxLength}});
        {{/maxLength}}
            {{pads}}}
    {{/isString}}
    {{#isEmail}}
        {{#isRequired}}
            {{pads}}if (!$request->{{fieldName}}->isValid()) {
            {{pads}}    $errors->{{fieldName}} = "GENERAL_ERROR_EMAIL_EMPTY";
        {{/isRequired}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if ($request->{{fieldName}}->hasValue() && !$request->{{fieldName}}->isValidEmail()) {
            {{pads}}    $errors->{{fieldName}} = "GENERAL_ERROR_EMAIL_INVALID";
        {{#isUnique}}
            {{pads}}} elseif (self::{{fieldName}}Exists($request->{{fieldName}}->get(){{parentsSecList}}, $id)) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}_EXISTS";
        {{/isUnique}}
            {{pads}}}
    {{/isEmail}}
    {{#isUrl}}
        {{#isRequired}}
            {{pads}}if (!$request->{{fieldName}}->isValid()) {
            {{pads}}    $errors->{{fieldName}} = "GENERAL_ERROR_URL_EMPTY";
        {{/isRequired}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if ($request->{{fieldName}}->hasValue() && !$request->{{fieldName}}->isValidUrl()) {
            {{pads}}    $errors->{{fieldName}} = "GENERAL_ERROR_URL_INVALID";
            {{pads}}}
    {{/isUrl}}
    {{#isNumber}}
        {{#isRequired}}
            {{pads}}if (!$request->{{fieldName}}->hasValue()) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}{{#emptySuffix}}_EMPTY{{/emptySuffix}}";
        {{/isRequired}}
        {{#typeOf}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if ($request->{{fieldName}}->hasValue() && !{{typeOf}}::{{method}}($request->{{fieldName}}->get())) {
            {{pads}}    $errors->{{fieldName}} = "{{typeOfError}}";
        {{/typeOf}}
        {{#belongsTo}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if ($request->{{fieldName}}->hasValue() && !{{belongsTo}}::{{method}}($request->{{fieldName}}->get(){{#withParent}}{{parentsSecList}}{{/withParent}})) {
            {{pads}}    $errors->{{fieldName}} = "{{belongsToError}}";
        {{/belongsTo}}
        {{#isNumeric}}
            {{pads}}{{#isRequired}}} else{{/isRequired}}if (!$request->{{fieldName}}->{{validFunc}}({{numericParams}})) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}{{#invalidPrefix}}_INVALID{{/invalidPrefix}}";
        {{/isNumeric}}
        {{#isUnique}}
            {{pads}}} elseif (self::{{fieldName}}Exists($request->{{fieldName}}->get(){{parentsSecList}}, $id)) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}_EXISTS";
        {{/isUnique}}
        {{#greaterThan}}
            {{pads}}} elseif (!$request->{{fieldName}}->isGreaterThan($request->{{greaterThan}})) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}_GREATER";
        {{/greaterThan}}
            {{pads}}}
    {{/isNumber}}
    {{#isPrice}}
            {{pads}}if (!$request->{{fieldName}}->isValidPrice(0)) {
            {{pads}}    $errors->{{fieldName}} = "{{fieldError}}";
            {{pads}}}
    {{/isPrice}}
    {{#isDate}}
            {{pads}}if (!$request->{{fieldName}}->hasValue()) {
            {{pads}}    $errors->{{dateName}} = "GENERAL_ERROR_{{errorText}}_DATE_EMPTY";
            {{pads}}} elseif (!$request->{{fieldName}}->isValid()) {
            {{pads}}    $errors->{{dateName}} = "GENERAL_ERROR_{{errorText}}_DATE_INVALID";
            {{pads}}} elseif (!$request->{{fieldName}}->hasHour()) {
            {{pads}}    $errors->{{dateName}} = "GENERAL_ERROR_{{errorText}}_HOUR_EMPTY";
            {{pads}}} elseif (!$request->{{fieldName}}->isValidHour()) {
            {{pads}}    $errors->{{dateName}} = "GENERAL_ERROR_{{errorText}}_HOUR_INVALID";
            {{pads}}}
        {{#hasPeriod}}
            {{pads}}if (!$request->{{fromDateName}}->isValidFullPeriod($request->{{toDateName}})) {
            {{pads}}    $errors->toDate = "GENERAL_ERROR_DATE_PERIOD";
            {{pads}}}
        {{/hasPeriod}}
    {{/isDate}}
            {{pads}}}
    {{#isStatus}}
            {{pads}}if (!{{statusClass}}::isValid($request->status->get())) {
            {{pads}}    $errors->status = "GENERAL_ERROR_STATUS";
            {{pads}}}
    {{/isStatus}}
    {{#hasIf}}
            }
    {{/hasIf}}

{{/validations}}
    {{#hasPositions}}
            if (!$request->position->isValid(0)) {
                $errors->position = "GENERAL_ERROR_POSITION";
            }

    {{/hasPositions}}
            $canValidate = true;
        }

        return new Result($canValidate, $errors);
    }
{{/hasValidation}}



    /**
     * Constructs the {{name}} Entity
     * @param Dictionary $data
     * @return {{entityClass}}
     */
    protected static function constructEntity(Dictionary $data): {{entityClass}} {
        $entity = new {{entityClass}}($data);
        {{#hasVirtual}}
        if ($entity->exists()) {
            $entity = static::postProcess($entity);
        }
        {{/hasVirtual}}
        return $entity;
    }

    /**
     * Constructs a list of {{name}} Entities
     * @param Dictionary $list
     * @return list<{{entityClass}}>
     */
    protected static function constructEntities(Dictionary $list): array {
        $result = [];
        foreach ($list as $data) {
            $result[] = self::constructEntity($data);
        }
        return $result;
    }

    /**
     * Creates the List Query
     * @param Request $request
     * @return {{queryClass}}
     */
    protected static function createListQuery(Request $request): {{queryClass}} {
        return new {{queryClass}}();
    }

{{#hasParents}}
    /**
     * Returns the Parents Query{{#parents}}
     * @param {{fieldDocNull}}{{/parents}}
     * @return {{queryClass}}
     */
    protected static function createParentQuery({{#parents}}
        {{fieldArgNull}},{{/parents}}
    ): {{queryClass}} {
        $query = new {{queryClass}}();
        {{#parents}}
        $query->getQuery()->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}}, {{fieldParam}} !== null);
        {{/parents}}
        return $query;
    }

{{/hasParents}}
{{#hasVirtual}}
    /**
     * Post Process the Result
     * @param {{entityClass}} ${{entityName}}
     * @return {{entityClass}}
     */
    protected static function postProcess({{entityClass}} ${{entityName}}): {{entityClass}} {
        return ${{entityName}};
    }

{{/hasVirtual}}


{{#hasID}}
    /**
     * Returns true if there is a {{name}} Entity with the given {{idText}}
     * @param {{idType}} ${{idName}}{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}{{#hasDeleted}}
     * @param bool $withDeleted Optional.{{/hasDeleted}}
     * @return bool
     */
    public static function exists(
        {{idType}} ${{idName}},{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}{{#hasDeleted}}
        bool $withDeleted = true,{{/hasDeleted}}
    ): bool {
        $query = Query::select(self::$tableName);
        $query->where("{{idDbName}}", QueryOperator::Equal, {{{idValue}}});
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        return self::getSchemaTotal($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}}) > 0;
    }

{{/hasID}}
{{#uniques}}
    /**
     * Returns true if there is a {{name}} Entity with the given {{fieldText}}
     * @param {{fieldDoc}}{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}
     * @param int $skipID Optional.
     * @return bool
     */
    public static function {{fieldName}}Exists(
        {{fieldArg}},{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}
        int $skipID = 0,
    ): bool {
        $query = Query::select(self::$tableName);
        $query->where("{{fieldKey}}", QueryOperator::Equal, {{{fieldValue}}});
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        $query->whereIf("{{idDbName}}", QueryOperator::NotEqual, $skipID);
        return self::getSchemaTotal($query) > 0;
    }

{{/uniques}}
    /**
     * Returns true if there is a {{name}} Entity with the given Query
     * @param {{queryClass}} $query{{#hasDeleted}}
     * @param bool $withDeleted Optional.{{/hasDeleted}}
     * @return bool
     */
    public static function entityExists({{queryClass}} $query{{#hasDeleted}}, bool $withDeleted = true{{/hasDeleted}}): bool {
        return self::getSchemaTotal($query{{#hasDeleted}}, $withDeleted{{/hasDeleted}}) > 0;
    }



{{#hasID}}
    /**
     * Returns the {{name}} Entity with the given ID
     * @param {{idType}} ${{idName}}{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}{{#canDelete}}
     * @param bool $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param bool $decrypted Optional.{{/hasEncrypt}}
     * @return {{entityClass}}
     */
    public static function getByID(
        {{idType}} ${{idName}},{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}{{#canDelete}}
        bool $withDeleted = true,{{/canDelete}}{{#hasEncrypt}}
        bool $decrypted = false,{{/hasEncrypt}}
    ): {{entityClass}} {
        $query = Query::select(self::$tableName);
        $query->where("{{idDbName}}", QueryOperator::Equal, {{{idValue}}});
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        $data  = self::getSchemaEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

{{/hasID}}
{{#uniques}}
    /**
     * Returns the {{name}} Entity with the given {{fieldText}}
     * @param {{fieldDoc}}{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}{{#canDelete}}
     * @param bool $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param bool $decrypted Optional.{{/hasEncrypt}}
     * @return {{entityClass}}
     */
    public static function getBy{{fieldText}}(
        {{fieldArg}},{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}{{#canDelete}}
        bool $withDeleted = true,{{/canDelete}}{{#hasEncrypt}}
        bool $decrypted = false,{{/hasEncrypt}}
    ): {{entityClass}} {
        $query = Query::select(self::$tableName);
        $query->where("{{fieldKey}}", QueryOperator::Equal, {{{fieldValue}}});
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        $data = self::getSchemaEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

{{/uniques}}
    /**
     * Returns the {{name}} Entity with the given Query
     * @param {{queryClass}} $query{{#canDelete}}
     * @param bool $withDeleted Optional.{{/canDelete}}{{#hasEncrypt}}
     * @param bool $decrypted Optional.{{/hasEncrypt}}
     * @return {{entityClass}}
     */
    protected static function getEntity(
        {{queryClass}} $query,{{#canDelete}}
        bool $withDeleted = true,{{/canDelete}}{{#hasEncrypt}}
        bool $decrypted = false,{{/hasEncrypt}}
    ): {{entityClass}} {
        $data = self::getSchemaEntity($query{{#canDelete}}, $withDeleted{{/canDelete}}{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}});
        return self::constructEntity($data);
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param {{queryClass}} $query
     * @param {{columnClass}} $column
     * @return string
     */
    protected static function getEntityValue(
        {{queryClass}} $query,
        {{columnClass}} $column,
    ): string {
        return self::getSchemaValue($query, $column->base());
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param {{queryClass}} $query
     * @param {{columnClass}} $column
     * @return array<int|string>
     */
    protected static function getEntityColumn(
        {{queryClass}} $query,
        {{columnClass}} $column,
    ): array {
        return self::getSchemaColumn($query, $column->name(), $column->key());
    }



{{#hasID}}
    /**
     * Returns the {{idText}} for the given query
     * @param {{queryClass}} $query
     * @return {{idType}}
     */
    public static function get{{idText}}({{queryClass}} $query): {{idType}} {
        $result = self::getSchemaValue($query, "{{idDbName}}");
        {{#hasStringID}}
        return $result;
        {{/hasStringID}}
        {{#hasIntID}}
        return Numbers::toInt($result);
        {{/hasIntID}}
        {{#hasEnumID}}
        return {{idEnumClass}}::fromValue($result);
        {{/hasEnumID}}
    }

    /**
     * Returns an array with all the {{idText}}s
     * @param {{queryClass}}|null $query Optional.
     * @return list<{{idType}}>
     */
    public static function get{{idText}}s(?{{queryClass}} $query = null): array {
        $result = self::getSchemaColumn($query, "{{idDbName}}", "{{idName}}");
        {{#hasIntID}}
        return Arrays::toInts($result);
        {{/hasIntID}}
        {{#hasStringID}}
        return Arrays::toStrings($result);
        {{/hasStringID}}
        {{#hasEnumID}}
        return {{idEnumClass}}::fromList(Arrays::toStrings($result));
        {{/hasEnumID}}
    }

{{/hasID}}
{{#hasList}}
    /**
     * Returns a list of {{name}} Entities
     * @param Request $request{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}
     * @return list<{{entityClass}}>
     */
    public static function getList(
        Request $request,{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}
    ): array {
        $query = static::createListQuery($request)->getQuery();
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        $list  = self::getSchemaEntities($query, sort: $request);
        return self::constructEntities($list);
    }

{{/hasList}}
    /**
     * Returns an array of {{name}} Entities
     * @param {{queryClass}}|null $query Optional.
     * @param Request|null $sort Optional.
     * @param array<string,string> $selects Optional.
     * @param list<string> $joins Optional.{{#hasEncrypt}}
     * @param bool $decrypted Optional.{{/hasEncrypt}}
     * @param bool $skipSubRequest Optional.
     * @return list<{{entityClass}}>
     */
    protected static function getEntityList(
        ?{{queryClass}} $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],{{#hasEncrypt}}
        bool $decrypted = false,{{/hasEncrypt}}
        bool $skipSubRequest = false,
    ): array {
        $list = self::getSchemaEntities($query, $sort, $selects, $joins{{#hasEncrypt}}, decrypted: $decrypted{{/hasEncrypt}}, skipSubRequest: $skipSubRequest);
        return self::constructEntities($list);
    }

{{#hasList}}
    /**
     * Returns a Total number of {{name}} Entities
     * @param Request $request{{#parents}}
     * @param {{fieldDocDefault}} Optional.{{/parents}}
     * @return int
     */
    public static function getTotal(
        Request $request,{{#parents}}
        {{{fieldArgDefault}}},{{/parents}}
    ): int {
        $query = static::createListQuery($request)->getQuery();
        {{#parents}}
        $query->whereIf("{{fieldKey}}", QueryOperator::Equal, {{{fieldValueNull}}});
        {{/parents}}
        return self::getSchemaTotal($query);
    }

{{/hasList}}
    /**
     * Returns a Total number of {{name}} Entities
     * @param {{queryClass}}|null $query Optional.
     * @return int
     */
    public static function getEntityTotal(?{{queryClass}} $query = null): int {
        return self::getSchemaTotal($query);
    }



    /**
     * Returns a Select of {{name}} Entities
     * @param {{queryClass}} $query
     * @param list<{{columnClass}}>|{{columnClass}} $nameColumn
     * @param {{columnClass}}|null $idColumn Optional.
     * @param {{columnClass}}|null $descColumn Optional.
     * @param list<{{columnClass}}>|{{columnClass}}|null $extraColumn Optional.
     * @param {{columnClass}}|null $distinctColumn Optional.
     * @param bool $useEmpty Optional.
     * @return list<Select>
     */
    protected static function getEntitySelect(
        {{queryClass}} $query,
        array|{{columnClass}} $nameColumn,
        ?{{columnClass}} $idColumn = null,
        ?{{columnClass}} $descColumn = null,
        array|{{columnClass}}|null $extraColumn = null,
        ?{{columnClass}} $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        return self::getSchemaSelect(
            $query,
            nameColumn:     {{columnClass}}::toKeys($nameColumn),
            idColumn:       $idColumn?->key(),
            descColumn:     $descColumn?->key(),
            extraColumn:    {{columnClass}}::toKeys($extraColumn),
            distinctColumn: $distinctColumn?->name(),
            useEmpty:       $useEmpty,
        );
    }

    /**
     * Returns the Search results for the {{name}} Entities
     * @param {{queryClass}} $query
     * @param list<{{columnClass}}>|{{columnClass}} $nameColumn
     * @param {{columnClass}}|null $idColumn Optional.
     * @param int $limit Optional.
     * @return list<Search>
     */
    public static function getEntitySearch(
        {{queryClass}} $query,
        array|{{columnClass}} $nameColumn,
        ?{{columnClass}} $idColumn = null,
        int $limit = 0,
    ): array {
        return self::getSchemaSearch(
            $query,
            nameColumn: {{columnClass}}::toKeys($nameColumn),
            idColumn:   $idColumn?->key(),
            limit:      $limit,
        );
    }


{{#canCreate}}

    /**
     * Creates a new {{name}} Entity{{#hasRequest}}
     * @param {{requestClass}}|null $entityRequest Optional.{{/hasRequest}}{{#fields}}
     * @param {{{fieldDocNull}}} Optional.{{/fields}}{{#hasStatus}}
     * @param {{statusClass}}|null $status Optional.{{/hasStatus}}{{#hasTimestamps}}
     * @param Date|null $createdTime Optional.{{/hasTimestamps}}{{#hasUsers}}
     * @param int $createdUser Optional.{{/hasUsers}}{{#hasPositions}}
     * @param bool $skipOrder Optional.{{/hasPositions}}
     * @return int
     */
    protected static function createEntity({{#hasRequest}}
        ?{{requestClass}} $entityRequest = null,{{/hasRequest}}{{#fields}}
        {{fieldArgCreate}},{{/fields}}{{#hasStatus}}
        ?{{statusClass}} $status = null,{{/hasStatus}}{{#hasTimestamps}}
        ?Date $createdTime = null,{{/hasTimestamps}}{{#hasUsers}}
        int $createdUser = 0,{{/hasUsers}}{{#hasPositions}}
        bool $skipOrder = false,{{/hasPositions}}
    ): int {
        $fields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $fields["{{fieldKey}}"] = {{{fieldAssign}}};
        }{{#fieldIsRequested}} elseif ($entityRequest !== null) {
            $fields["{{fieldKey}}"] = $entityRequest->{{fieldName}};
        }{{/fieldIsRequested}}
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $fields["status"] = $status;
        }{{#hasStatusRequest}} elseif ($entityRequest !== null) {
            $fields["status"] = $entityRequest->status;
        }{{/hasStatusRequest}}
        {{/hasStatus}}
        {{#hasTimestamps}}
        if ($createdTime !== null) {
            $fields["createdTime"] = $createdTime;
        }
        {{/hasTimestamps}}
        {{#hasUsers}}
        if ($createdUser === 0) {
            $createdUser = Auth::getID();
        }
        {{/hasUsers}}

        {{#hasPositions}}
        if ($skipOrder) {
            return self::createSchemaEntity(
                fields: $fields,{{#hasUsers}}
                credentialID: $createdUser,{{/hasUsers}}
            );
        }
        return self::createSchemaEntityWithOrder(
            fields: $fields,{{#hasEditParents}}
            orderQuery: self::createParentQuery({{parentsList}}),{{/hasEditParents}}{{#hasUsers}}
            credentialID: $createdUser,{{/hasUsers}}
        );
        {{/hasPositions}}
        {{^hasPositions}}
        return self::createSchemaEntity(
            fields: $fields,{{#hasUsers}}
            credentialID: $createdUser,{{/hasUsers}}
        );
        {{/hasPositions}}
    }
{{/canCreate}}
{{#canReplace}}

    /**
     * Replaces the {{name}} Entity{{#hasRequest}}
     * @param {{requestClass}}|null $entityRequest Optional.{{/hasRequest}}{{#fields}}
     * @param {{{fieldDocNull}}} Optional.{{/fields}}{{#hasStatus}}
     * @param {{statusClass}}|null $status Optional.{{/hasStatus}}{{#hasUsers}}
     * @param int $createdUser Optional.{{/hasUsers}}
     * @return bool
     */
    protected static function replaceEntity({{#hasRequest}}
        ?{{requestClass}} $entityRequest = null,{{/hasRequest}}{{#fields}}
        {{fieldArgCreate}},{{/fields}}{{#hasStatus}}
        ?{{statusClass}} $status = null,{{/hasStatus}}{{#hasUsers}}
        int $createdUser = 0,{{/hasUsers}}
    ): bool {
        $fields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $fields["{{fieldKey}}"] = {{{fieldAssign}}};
        }{{#fieldIsRequested}} elseif ($entityRequest !== null) {
            $fields["{{fieldKey}}"] = $entityRequest->{{fieldName}};
        }{{/fieldIsRequested}}
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $fields["status"] = $status;
        }{{#hasStatusRequest}} elseif ($entityRequest !== null) {
            $fields["status"] = $entityRequest->status;
        }{{/hasStatusRequest}}
        {{/hasStatus}}
        {{#hasUsers}}
        if ($createdUser === 0) {
            $createdUser = Auth::getID();
        }
        {{/hasUsers}}

        $result = self::replaceSchemaEntity(
            fields: $fields,{{#hasUsers}}
            credentialID: $createdUser,{{/hasUsers}}
        );
        return $result > 0;
    }
{{/canReplace}}
{{#canEdit}}

    /**
     * Edits a {{name}} Entity
     * @param {{editType}} $query{{#hasRequest}}
     * @param {{requestClass}}|null $entityRequest Optional.{{/hasRequest}}{{#fields}}
     * @param {{{fieldDocEdit}}} Optional.{{/fields}}{{#hasStatus}}
     * @param {{statusClass}}|null $status Optional.{{/hasStatus}}{{#hasUsers}}
     * @param int $modifiedUser Optional.{{/hasUsers}}{{#canDelete}}
     * @param bool|null $isDeleted Optional.{{/canDelete}}{{#hasPositions}}
     * @param bool $skipOrder Optional.{{/hasPositions}}{{#hasTimestamps}}
     * @param bool $skipTimestamps Optional.{{/hasTimestamps}}
     * @return bool
     */
    protected static function editEntity(
        {{editType}} $query,{{#hasRequest}}
        ?{{requestClass}} $entityRequest = null,{{/hasRequest}}{{#fields}}
        {{fieldArgEdit}},{{/fields}}{{#hasStatus}}
        ?{{statusClass}} $status = null,{{/hasStatus}}{{#hasUsers}}
        int $modifiedUser = 0,{{/hasUsers}}{{#canDelete}}
        ?bool $isDeleted = null,{{/canDelete}}{{#hasPositions}}
        bool $skipOrder = false,{{/hasPositions}}{{#hasTimestamps}}
        bool $skipTimestamps = false,{{/hasTimestamps}}
    ): bool {
        $fields = [];
        {{#fields}}
        if ({{fieldParam}} !== null) {
            $fields["{{fieldKey}}"] = {{{fieldAssignEdit}}};
        }{{#fieldIsRequested}} elseif ($entityRequest !== null) {
            $fields["{{fieldKey}}"] = $entityRequest->{{fieldName}};
        }{{/fieldIsRequested}}
        {{/fields}}
        {{#hasStatus}}
        if ($status !== null) {
            $fields["status"] = $status;
        }{{#hasStatusRequest}} elseif ($entityRequest !== null) {
            $fields["status"] = $entityRequest->status;
        }{{/hasStatusRequest}}
        {{/hasStatus}}
        {{#canDelete}}
        if ($isDeleted !== null) {
            $fields["isDeleted"] = $isDeleted;
        }
        {{/canDelete}}
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }
        {{/hasUsers}}

        {{#hasPositions}}
        if ($skipOrder) {
            return self::editSchemaEntity(
                query: $query,
                fields: $fields,{{#hasUsers}}
                credentialID: $modifiedUser,{{/hasUsers}}{{#hasTimestamps}}
                skipTimestamps: $skipTimestamps,{{/hasTimestamps}}
            );
        }
        return self::editSchemaEntityWithOrder(
            query: $query,
            fields: $fields,{{#hasEditParents}}
            orderQuery: self::createParentQuery({{parentsList}}),{{/hasEditParents}}{{#hasUsers}}
            credentialID: $modifiedUser,{{/hasUsers}}{{#hasTimestamps}}
            skipTimestamps: $skipTimestamps,{{/hasTimestamps}}
        );
        {{/hasPositions}}
        {{^hasPositions}}
        return self::editSchemaEntity(
            query: $query,
            fields: $fields,{{#hasUsers}}
            credentialID: $modifiedUser,{{/hasUsers}}{{#hasTimestamps}}
            skipTimestamps: $skipTimestamps,{{/hasTimestamps}}
        );
        {{/hasPositions}}
    }

    /**
     * Edits a value in a {{name}} Entity
     * @param {{editType}} $query
     * @param {{columnClass}} $column
     * @param int|string $value{{#hasUsers}}
     * @param int $modifiedUser Optional.{{/hasUsers}}
     * @return bool
     */
    protected static function editEntityValue(
        {{editType}} $query,
        {{columnClass}} $column,
        int|string $value,{{#hasUsers}}
        int $modifiedUser = 0,{{/hasUsers}}
    ): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        return self::editSchemaEntity($query, fields: [
            $column->base() => $value,
        ]{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
    }

    /**
     * Increments a value in a {{name}} Entity
     * @param {{editType}} $query
     * @param {{columnClass}} $column
     * @param int $amount Optional.{{#hasUsers}}
     * @param int $modifiedUser Optional.{{/hasUsers}}
     * @return bool
     */
    protected static function increaseEntity(
        {{editType}} $query,
        {{columnClass}} $column,
        int $amount = 1,{{#hasUsers}}
        int $modifiedUser = 0,{{/hasUsers}}
    ): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        return self::editSchemaEntity($query, fields: [
            $column->base() => Assign::increase($amount),
        ]{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
    }
{{/canEdit}}
{{#canDelete}}

    /**
     * Deletes the {{name}} Entity
     * @param {{editType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}{{#hasUsers}}
     * @param int $modifiedUser Optional.{{/hasUsers}}{{#hasPositions}}
     * @param bool $skipOrder Optional.{{/hasPositions}}
     * @return bool
     */
    protected static function deleteEntity(
        {{editType}} $query,{{#editParents}}
        {{fieldArg}},{{/editParents}}{{#hasUsers}}
        int $modifiedUser = 0,{{/hasUsers}}{{#hasPositions}}
        bool $skipOrder = false,{{/hasPositions}}
    ): bool {
        {{#hasUsers}}
        if ($modifiedUser === 0) {
            $modifiedUser = Auth::getID();
        }

        {{/hasUsers}}
        {{#hasPositions}}
        if ($skipOrder) {
            return self::deleteSchemaEntity($query{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        }
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::deleteSchemaEntityWithOrder(
            $query,{{#hasEditParents}}
            orderQuery: $orderQuery,{{/hasEditParents}}{{#hasUsers}}
            credentialID: $modifiedUser,{{/hasUsers}}
        );
        {{/hasPositions}}
        {{^hasPositions}}
        return self::deleteSchemaEntity($query{{#hasUsers}}, credentialID: $modifiedUser{{/hasUsers}});
        {{/hasPositions}}
    }
{{/canDelete}}

    /**
     * Removes the {{name}} Entity
     * @param {{editType}} $query{{#editParents}}
     * @param {{fieldDoc}}{{/editParents}}
     * @return bool
     */
    protected static function removeEntity(
        {{editType}} $query,{{#editParents}}
        {{fieldArg}},{{/editParents}}
    ): bool {
        {{#hasPositions}}
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::removeSchemaEntityWithOrder($query{{#hasEditParents}}, $orderQuery{{/hasEditParents}});
        {{/hasPositions}}
        {{^hasPositions}}
        return self::removeSchemaEntity($query);
        {{/hasPositions}}
    }

{{#hasPositions}}
    /**
     * Removes the {{name}} Entity
     * @param {{editType}} $query
     * @return bool
     */
    protected static function removeAllEntities(
        {{editType}} $query,
    ): bool {
        return self::removeSchemaEntity($query);
    }

{{#hasRequest}}
    /**
     * Ensures that the order of the Elements is correct
     * @param {{entityClass}}|null $entity
     * @param {{requestClass}}|null $fields{{#parents}}
     * @param {{fieldDoc}}{{/parents}}
     * @return int
     */
    protected static function ensureEntityOrder(
        ?{{entityClass}} $entity,
        {{requestClass}}|null $fields,{{#editParents}}
        {{fieldArg}},{{/editParents}}
    ): int {
        {{#hasEditParents}}
        $orderQuery = self::createParentQuery({{parentsList}});
        {{/hasEditParents}}
        return self::ensureSchemaOrder(
            oldFields: $entity?->toDictionary(),
            newFields: $fields?->toDictionary(),{{#hasEditParents}}
            query:     $orderQuery,{{/hasEditParents}}
        );
    }

{{/hasRequest}}
{{/hasPositions}}
    /**
     * Ensures that only one Entity has the Unique column set
     * @param {{queryClass}} $query
     * @param {{columnClass}} $column
     * @param int $id
     * @param int $oldValue
     * @param int $newValue
     * @return bool
     */
    protected static function ensureUniqueData(
        {{queryClass}} $query,
        {{columnClass}} $column,
        int $id,
        int $oldValue,
        int $newValue,
    ): bool {
        return self::ensureSchemaUniqueData($query, $column->base(), $id, $oldValue, $newValue);
    }
}
