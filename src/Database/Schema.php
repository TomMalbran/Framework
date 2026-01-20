<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Request;
use Framework\Database\SchemaModel;
use Framework\Database\Selection;
use Framework\Database\Modification;
use Framework\Database\Query\Query;
use Framework\Database\Query\QueryOperator;
use Framework\Database\Model\SubRequest;
use Framework\Database\Type\Assign;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Search;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The Schema
 */
class Schema {

    protected static ?SchemaModel $model = null;

    protected static string $modelName      = "";
    protected static string $tableName      = "";
    protected static string $idName         = "";
    protected static string $idDbName       = "";

    protected static bool   $hasPositions   = false;
    protected static bool   $canDelete      = false;
    protected static bool   $hasSubRequests = false;



    /**
     * Returns the Model
     * @return SchemaModel
     */
    public static function getModel(): SchemaModel {
        if (static::$model === null) {
            static::$model = new SchemaModel();
        }
        return static::$model;
    }

    /**
     * Returns a list of SubRequests
     * @return SubRequest[]
     */
    public static function getSubRequests(): array {
        return [];
    }

    /**
     * Replaces the Table in the Expression
     * @param string $expression
     * @return string
     */
    private static function replaceTable(string $expression): string {
        $tableName = static::$tableName;
        return Strings::replace($expression, "{table}", "`{$tableName}`");
    }



    /**
     * Returns true if a Table exists
     * @return bool
     */
    public static function tableExists(): bool {
        return Framework::getDatabase()->tableExists(static::$tableName);
    }

    /**
     * Returns true if the Schema has a Primary Key
     * @return bool
     */
    public static function hasPrimaryKey(): bool {
        $keys = Framework::getDatabase()->getPrimaryKeys(static::$tableName);
        return count($keys) > 0;
    }

    /**
     * Encrypts the given Value
     * @param string $value
     * @return Assign
     */
    protected static function encrypt(string $value): Assign {
        return Assign::encrypt($value, Config::getDbKey());
    }



    /**
     * Returns the Data of an Entity with the given ID or Query
     * @param Query|int|string $query
     * @param bool             $withDeleted Optional.
     * @param bool             $decrypted   Optional.
     * @return array{}
     */
    protected static function getSchemaEntity(
        Query|int|string $query,
        bool $withDeleted = true,
        bool $decrypted = false,
    ): array {
        $query   = self::generateQueryID($query, $withDeleted)->limit(1);
        $request = self::requestSchemaData($query, decrypted: $decrypted);
        return isset($request[0]) ? $request[0] : [];
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query  $query
     * @param string $column
     * @return string|int
     */
    protected static function getSchemaValue(Query $query, string $column): string|int {
        return Framework::getDatabase()->getValue(static::$tableName, $column, $query);
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query|null $query
     * @param string     $column
     * @param string     $columnKey Optional.
     * @return array<string|int>
     */
    protected static function getSchemaColumn(?Query $query, string $column, string $columnKey = ""): array {
        $query     = self::generateQuery($query);
        $selection = new Selection(static::getModel());
        $selection->addFields();
        $selection->addSelects($column, true);
        $selection->addJoins();

        $selection->request($query);
        $request = $selection->resolve();
        $result  = [];

        $columnKey = $columnKey !== "" ? $columnKey : Strings::substringAfter($column, ".");
        foreach ($request as $row) {
            if (!isset($row[$columnKey])) {
                continue;
            }
            $value = $row[$columnKey];
            if (!Arrays::isEmpty($value) && !Arrays::contains($result, $value)) {
                if (is_string($value) || is_int($value)) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Returns a Total using the Joins
     * @param Query|null $query       Optional.
     * @param bool       $withDeleted Optional.
     * @return int
     */
    protected static function getSchemaTotal(?Query $query = null, bool $withDeleted = true): int {
        $query     = self::generateQuery($query, $withDeleted);
        $selection = new Selection(static::getModel());
        $selection->addSelects("COUNT(*) AS cnt");
        $selection->addJoins(withSelects: false);

        $request = $selection->request($query);
        if (isset($request[0]["cnt"])) {
            return Numbers::toInt($request[0]["cnt"]);
        }
        return 0;
    }

    /**
     * Returns a Select array
     * @param Query                $query
     * @param string[]|string      $nameColumn
     * @param string|null          $idColumn       Optional.
     * @param string|null          $descColumn     Optional.
     * @param string[]|string|null $extraColumn    Optional.
     * @param string|null          $distinctColumn Optional.
     * @param bool                 $useEmpty       Optional.
     * @return Select[]
     */
    protected static function getSchemaSelect(
        Query $query,
        array|string $nameColumn,
        ?string $idColumn = null,
        ?string $descColumn = null,
        array|string|null $extraColumn = null,
        ?string $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        $query     = self::generateQuery($query);
        $selection = new Selection(static::getModel());
        if ($distinctColumn !== null) {
            $selection->addSelects("DISTINCT($distinctColumn)");
        }

        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->request($query);
        $request = $selection->resolve();

        return Select::create(
            $request,
            keyName:  $idColumn ?? static::$idName,
            valName:  $nameColumn,
            descName: $descColumn,
            extraKey: $extraColumn,
            useEmpty: $useEmpty,
            distinct: true,
        );
    }

    /**
     * Returns the Search results
     * @param Query           $query
     * @param string[]|string $nameColumn
     * @param string|null     $idColumn   Optional.
     * @param int             $limit      Optional.
     * @return Search[]
     */
    protected static function getSchemaSearch(
        Query $query,
        array|string $nameColumn,
        ?string $idColumn = null,
        int $limit = 0,
    ): array {
        $query   = self::generateQuery($query)->limit($limit);
        $request = self::requestSchemaData($query);

        if ($idColumn === null || $idColumn === "") {
            $idColumn = static::$idName;
        }
        return Search::create($request, $idColumn, $nameColumn);
    }



    /**
     * Returns the Data using a basic Expression and Params
     * @param string  $expression
     * @param mixed[] $params     Optional.
     * @return array<string,string|int>[]
     */
    protected static function getDataWithParams(string $expression, array $params = []): array {
        $expression = self::replaceTable($expression);
        $request    = Framework::getDatabase()->getData($expression, $params);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return array<string,string|int>[]
     */
    protected static function getSchemaData(Query $query, string $expression): array {
        $expression = self::replaceTable($expression);
        $request    = Framework::getDatabase()->getData($expression, $query);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return array<string,string|int>
     */
    protected static function getSchemaRow(Query $query, string $expression): array {
        $expression = self::replaceTable($expression);
        $request    = Framework::getDatabase()->getData($expression, $query);

        if (isset($request[0])) {
            return $request[0];
        }
        return [];
    }

    /**
     * Returns an array of Entities Data
     * @param Query|null   $query          Optional.
     * @param Request|null $sort           Optional.
     * @param array{}      $selects        Optional.
     * @param string[]     $joins          Optional.
     * @param bool         $decrypted      Optional.
     * @param bool         $skipSubRequest Optional.
     * @return array{}[]
     */
    protected static function getSchemaEntities(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
        bool $skipSubRequest = false,
    ): array {
        $query   = self::generateQuerySort($query, $sort);
        $request = self::requestSchemaData($query, $selects, $joins, $decrypted, $skipSubRequest);
        return $request;
    }

    /**
     * Requests data to the database
     * @param Query                $query
     * @param array<string,string> $selects        Optional.
     * @param string[]             $joins          Optional.
     * @param bool                 $decrypted      Optional.
     * @param bool                 $skipSubRequest Optional.
     * @return array{}[]
     */
    private static function requestSchemaData(
        Query $query,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
        bool $skipSubRequest = false,
    ): array {
        $selection = new Selection(static::getModel());
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();
        $selection->request($query);

        $result = $selection->resolve(array_keys($selects));
        if (!$skipSubRequest && static::$hasSubRequests) {
            foreach (static::getSubRequests() as $subRequest) {
                $result = $subRequest->request($result);
            }
        }
        return $result;
    }



    /**
     * Returns the expression of the Query
     * @param Query|null           $query     Optional.
     * @param Request|null         $sort      Optional.
     * @param array<string,string> $selects   Optional.
     * @param string[]             $joins     Optional.
     * @param bool                 $decrypted Optional.
     * @return string
     */
    protected static function getExpression(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
    ): string {
        $query     = self::generateQuerySort($query, $sort);
        $selection = new Selection(static::getModel());
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();

        $expression = $selection->getExpression($query);
        return Framework::getDatabase()->interpolateQuery($expression, $query->params);
    }

    /**
     * Returns the expression of the data Query
     * @param Query  $query
     * @param string $expression
     * @return string
     */
    protected static function getDataExpression(Query $query, string $expression): string {
        $expression = self::replaceTable($expression);
        return Framework::getDatabase()->interpolateQuery($expression, $query);
    }



    /**
     * Truncates all the Entities
     * @return bool
     */
    protected static function truncateData(): bool {
        return Framework::getDatabase()->truncate(static::$tableName);
    }

    /**
     * Creates a new Entity with data
     * @param Request|null        $request      Optional.
     * @param array<string,mixed> $fields       Optional.
     * @param int                 $credentialID Optional.
     * @return int
     */
    protected static function createSchemaEntity(
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
    ): int {
        $modification = new Modification(static::getModel());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();
        return $modification->insert();
    }

    /**
     * Replaces the Data of an Entity
     * @param Request|null        $request      Optional.
     * @param array<string,mixed> $fields       Optional.
     * @param int                 $credentialID Optional.
     * @return int
     */
    protected static function replaceSchemaEntity(
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
    ): int {
        $modification = new Modification(static::getModel());
        $modification->addFields($request, $fields);
        $modification->addModification($credentialID);
        return $modification->replace();
    }

    /**
     * Edits the Data of an Entity
     * @param Query|int|string    $query
     * @param Request|null        $request        Optional.
     * @param array<string,mixed> $fields         Optional.
     * @param int                 $credentialID   Optional.
     * @param bool                $skipTimestamps Optional.
     * @param bool                $skipEmpty      Optional.
     * @param bool                $skipUnset      Optional.
     * @return bool
     */
    protected static function editSchemaEntity(
        Query|int|string $query,
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
        bool $skipTimestamps = false,
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): bool {
        $modification = new Modification(static::getModel());
        $modification->addFields($request, $fields, $skipEmpty, $skipUnset);
        if (!$skipTimestamps) {
            $modification->addModification($credentialID);
        }

        $query = self::generateQueryID($query, false);
        return $modification->update($query);
    }

    /**
     * Deletes the Data of an Entity
     * @param Query|int|string $query
     * @param int              $credentialID Optional.
     * @return bool
     */
    protected static function deleteSchemaEntity(Query|int|string $query, int $credentialID = 0): bool {
        $query = self::generateQueryID($query, false);
        if (static::$canDelete) {
            return self::editSchemaEntity($query, null, [ "isDeleted" => 1 ], $credentialID);
        }
        return false;
    }

    /**
     * Removes the Data of an Entity
     * @param Query|int|string $query
     * @return bool
     */
    protected static function removeSchemaEntity(Query|int|string $query): bool {
        $query = self::generateQueryID($query, false);
        return Framework::getDatabase()->delete(static::$tableName, $query);
    }



    /**
     * Creates an Entity and ensures the Order
     * @param Request|null        $request      Optional.
     * @param array<string,mixed> $fields       Optional.
     * @param int                 $credentialID Optional.
     * @param Query|null          $orderQuery   Optional.
     * @return int
     */
    protected static function createSchemaEntityWithOrder(
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
        ?Query $orderQuery = null,
    ): int {
        $modification = new Modification(static::getModel());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();

        $newPosition = self::ensureSchemaOrder(null, $modification->getFields(), $orderQuery);
        $modification->setField("position", $newPosition);
        return $modification->insert();
    }

    /**
     * Edits the Data of an Entity and ensures the Order
     * @param Query|int|string    $query
     * @param Request|null        $request        Optional.
     * @param array<string,mixed> $fields         Optional.
     * @param int                 $credentialID   Optional.
     * @param Query|null          $orderQuery     Optional.
     * @param bool                $skipTimestamps Optional.
     * @param bool                $skipEmpty      Optional.
     * @param bool                $skipUnset      Optional.
     * @return bool
     */
    protected static function editSchemaEntityWithOrder(
        Query|int|string $query,
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
        ?Query $orderQuery = null,
        bool $skipTimestamps = false,
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): bool {
        $modification = new Modification(static::getModel());
        $modification->addFields($request, $fields, $skipEmpty, $skipUnset);
        if (!$skipTimestamps) {
            $modification->addModification($credentialID);
        }

        $fields = $modification->getFields();
        if (isset($fields["position"])) {
            $elem         = self::getSchemaEntity($query);
            $savePosition = self::ensureSchemaOrder($elem, $fields, $orderQuery);
            $modification->setField("position", $savePosition);
        }

        $query = self::generateQueryID($query, false);
        return $modification->update($query);
    }

    /**
     * Deletes the Data of an Entity and ensures the Order
     * @param Query|int|string $query
     * @param int              $credentialID Optional.
     * @param Query|null       $orderQuery   Optional.
     * @return bool
     */
    protected static function deleteSchemaEntityWithOrder(
        Query|int|string $query,
        int $credentialID = 0,
        ?Query $orderQuery = null,
    ): bool {
        $elem = self::getSchemaEntity($query);
        if (self::deleteSchemaEntity($query, $credentialID)) {
            self::ensureSchemaOrder($elem, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Removes the Data of an Entity and ensures the Order
     * @param Query|int|string $query
     * @param Query|null       $orderQuery Optional.
     * @return bool
     */
    protected static function removeSchemaEntityWithOrder(Query|int|string $query, ?Query $orderQuery = null): bool {
        $elem = self::getSchemaEntity($query);
        if (self::removeSchemaEntity($query)) {
            self::ensureSchemaOrder($elem, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Ensures that the Order of the Schema is correct
     * @param mixed      $oldFields
     * @param mixed      $newFields
     * @param Query|null $query     Optional.
     * @return int
     */
    protected static function ensureSchemaOrder(mixed $oldFields, mixed $newFields, ?Query $query = null): int {
        $isCreate     = Arrays::isEmpty($oldFields)  && !Arrays::isEmpty($newFields);
        $isEdit       = !Arrays::isEmpty($oldFields) && !Arrays::isEmpty($newFields);
        $isDelete     = !Arrays::isEmpty($oldFields) && Arrays::isEmpty($newFields);

        $oldPosition  = Numbers::toInt(Arrays::getOneValue($oldFields, "position", default: 0));
        $newPosition  = Numbers::toInt(Arrays::getOneValue($newFields, "position", default: 0));
        $nextPosition = self::getNextPosition($query);

        $oldPosition  = $isCreate ? $nextPosition : $oldPosition;
        $newPosition  = $newPosition > 0 ? $newPosition : $nextPosition - ($isEdit ? 1 : 0);
        $savePosition = $newPosition;

        if ($isCreate && ($newPosition === 0 || $newPosition >= $nextPosition)) {
            return $nextPosition;
        }
        if (!$isDelete && $oldPosition === $newPosition) {
            return $newPosition;
        }

        if ($isEdit && $newPosition > $nextPosition) {
            $savePosition = $nextPosition - 1;
        }

        $newQuery = self::generateQuery($query);
        if ($newPosition > $oldPosition) {
            $newQuery->add("position", QueryOperator::GreaterThan, $oldPosition);
            $newQuery->add("position", QueryOperator::LessOrEqual, $newPosition);
            $assign = Assign::decrease(1);
        } else {
            $newQuery->add("position", QueryOperator::GreaterOrEqual, $newPosition);
            $newQuery->add("position", QueryOperator::LessThan, $oldPosition);
            $assign = Assign::increase(1);
        }

        Framework::getDatabase()->update(static::$tableName, [
            "position" => $assign,
        ], $newQuery);
        return $savePosition;
    }

    /**
     * Gets the Next Position
     * @param Query|null $query       Optional.
     * @param bool       $withDeleted Optional.
     * @return int
     */
    private static function getNextPosition(?Query $query = null, bool $withDeleted = true): int {
        if (!static::$hasPositions) {
            return 0;
        }

        $selection = new Selection(static::getModel());
        $selection->addSelects("position", true);
        $selection->addJoins(withSelects: false);

        $query = self::generateQuery($query, $withDeleted);
        $query->orderBy("position", false);
        $query->limit(1);

        $request = $selection->request($query);
        if (isset($request[0]) && isset($request[0]["position"])) {
            return Numbers::toInt($request[0]["position"]) + 1;
        }
        return 1;
    }

    /**
     * Ensures that only one Entity has the Unique column set
     * @param Query  $query
     * @param string $column
     * @param int    $id
     * @param int    $oldValue
     * @param int    $newValue
     * @return bool
     */
    protected static function ensureSchemaUniqueData(
        Query $query,
        string $column,
        int $id,
        int $oldValue,
        int $newValue,
    ): bool {
        $updated = false;
        if ($newValue !== 0 && $oldValue === 0) {
            $newQuery = new Query($query);
            $newQuery->add(static::$idDbName, QueryOperator::NotEqual, $id);
            $newQuery->add($column, QueryOperator::Equal, 1);
            self::editSchemaEntity($newQuery, null, [ $column => 0 ]);
            $updated = true;
        }
        if ($newValue === 0 && $oldValue !== 0) {
            $newQuery = self::generateQuery($query, true);
            $newQuery->limit(1);
            self::editSchemaEntity($newQuery, null, [ $column => 1 ]);
            $updated = true;
        }
        return $updated;
    }



    /**
     * Generates a Query with the ID or returns the Query
     * @param Query|int|string $query
     * @param bool             $withDeleted Optional.
     * @return Query
     */
    private static function generateQueryID(Query|int|string $query, bool $withDeleted = true): Query {
        if (!($query instanceof Query)) {
            $query = Query::create(static::$idDbName, QueryOperator::Equal, $query);
        }
        return self::generateQuery($query, $withDeleted);
    }

    /**
     * Generates a Query with Sorting
     * @param Query|null   $query Optional.
     * @param Request|null $sort  Optional.
     * @return Query
     */
    private static function generateQuerySort(?Query $query = null, ?Request $sort = null): Query {
        $query = self::generateQuery($query);

        if ($sort !== null) {
            if ($sort->has("orderBy")) {
                $query->orderBy($sort->getString("orderBy"), $sort->has("orderAsc"));
            }
            if ($sort->exists("page")) {
                $query->paginate($sort->getInt("page"), $sort->getInt("amount"));
            }
        } elseif (!$query->hasOrder() && static::$idDbName !== "") {
            $query->orderBy(static::$idDbName, true);
        }
        return $query;
    }

    /**
     * Generates a Query without Deleted if required
     * @param Query|null $query       Optional.
     * @param bool       $withDeleted Optional.
     * @return Query
     */
    private static function generateQuery(?Query $query = null, bool $withDeleted = true): Query {
        $query     = new Query($query);
        $isDeleted = static::getModel()->getKey("isDeleted");

        if ($withDeleted && static::$canDelete && !$query->hasColumn($isDeleted) && !$query->hasColumn("isDeleted")) {
            $query->add($isDeleted, QueryOperator::Equal, 0);
        }
        return $query;
    }
}
