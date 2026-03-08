<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Request;
use Framework\Database\SchemaModel;
use Framework\Database\Query\Query;
use Framework\Database\Query\Assign;
use Framework\Database\Query\Operator;
use Framework\Database\Query\SelectionBuilder;
use Framework\Database\Query\ModificationBuilder;
use Framework\Database\Model\SubRequest;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Search;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The Schema
 */
class Schema {

    protected static ?SchemaModel $model = null;

    protected static string $modelName = "";
    protected static string $tableName = "";
    protected static string $idName    = "";
    protected static string $idDbName  = "";
    protected static bool   $canDelete = false;



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
     * @return list<SubRequest>
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
     * @return Dictionary
     */
    protected static function getSchemaEntity(
        Query|int|string $query,
        bool $withDeleted = true,
        bool $decrypted = false,
    ): Dictionary {
        $query   = self::generateQueryID($query, $withDeleted)->limit(1);
        $request = self::requestSchemaData($query, decrypted: $decrypted);
        return $request->getFirst();
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query  $query
     * @param string $column
     * @return int|string
     */
    protected static function getSchemaValue(Query $query, string $column): int|string {
        return Framework::getDatabase()->getValue(static::$tableName, $column, $query);
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query|null $query
     * @param string     $column
     * @param string     $columnKey Optional.
     * @return array<int|string>
     */
    protected static function getSchemaColumn(?Query $query, string $column, string $columnKey = ""): array {
        $request = SelectionBuilder::create(static::getModel())
            ->addFields()
            ->addSelects($column, addMainKey: true)
            ->addJoins()
            ->request(self::generateQuery($query))
            ->resolve();

        $result    = [];
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
        $request = SelectionBuilder::create(static::getModel())
            ->addSelects("COUNT(*) AS cnt")
            ->addJoins(withSelects: false)
            ->request(self::generateQuery($query, $withDeleted))
            ->getResult();

        if (isset($request[0]["cnt"])) {
            return Numbers::toInt($request[0]["cnt"]);
        }
        return 0;
    }

    /**
     * Returns a Select array
     * @param Query                    $query
     * @param list<string>|string      $nameColumn
     * @param string|null              $idColumn       Optional.
     * @param string|null              $descColumn     Optional.
     * @param list<string>|string|null $extraColumn    Optional.
     * @param string|null              $distinctColumn Optional.
     * @param bool                     $useEmpty       Optional.
     * @return list<Select>
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
        $request = SelectionBuilder::create(static::getModel())
            ->addSelects($distinctColumn !== null ? "DISTINCT($distinctColumn)" : "")
            ->addFields()
            ->addExpressions()
            ->addJoins()
            ->request(self::generateQuery($query))
            ->resolve();

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
     * @param Query               $query
     * @param list<string>|string $nameColumn
     * @param string|null         $idColumn   Optional.
     * @param int                 $limit      Optional.
     * @return list<Search>
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
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return Dictionary
     */
    protected static function getSchemaData(Query $query, string $expression): Dictionary {
        $expression = self::replaceTable($expression);
        $request    = Framework::getDatabase()->getDictionary($expression, $query);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return Dictionary
     */
    protected static function getSchemaRow(Query $query, string $expression): Dictionary {
        $expression = self::replaceTable($expression);
        $request    = Framework::getDatabase()->getDictionary($expression, $query);
        return $request->getFirst();
    }

    /**
     * Returns an array of Entities Data
     * @param Query|null           $query          Optional.
     * @param Request|null         $sort           Optional.
     * @param array<string,string> $selects        Optional.
     * @param list<string>         $joins          Optional.
     * @param bool                 $decrypted      Optional.
     * @param bool                 $skipSubRequest Optional.
     * @return Dictionary
     */
    protected static function getSchemaEntities(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
        bool $skipSubRequest = false,
    ): Dictionary {
        $query   = self::generateQuerySort($query, $sort);
        $request = self::requestSchemaData($query, $selects, $joins, $decrypted, $skipSubRequest);
        return $request;
    }

    /**
     * Requests data to the database
     * @param Query                $query
     * @param array<string,string> $selects        Optional.
     * @param list<string>         $joins          Optional.
     * @param bool                 $decrypted      Optional.
     * @param bool                 $skipSubRequest Optional.
     * @return Dictionary
     */
    private static function requestSchemaData(
        Query $query,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
        bool $skipSubRequest = false,
    ): Dictionary {
        $request = SelectionBuilder::create(static::getModel())
            ->addFields($decrypted)
            ->addExpressions()
            ->addSelects(Arrays::getValues($selects))
            ->addJoins($joins)
            ->addCounts()
            ->request($query)
            ->resolve(array_keys($selects));

        if (!$skipSubRequest) {
            foreach (static::getSubRequests() as $subRequest) {
                $request = $subRequest->request($request);
            }
        }
        return new Dictionary($request);
    }



    /**
     * Returns the expression of the Query
     * @param Query|null           $query     Optional.
     * @param Request|null         $sort      Optional.
     * @param array<string,string> $selects   Optional.
     * @param list<string>         $joins     Optional.
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
        $query      = self::generateQuerySort($query, $sort);
        $expression = SelectionBuilder::create(static::getModel())
            ->addFields($decrypted)
            ->addExpressions()
            ->addSelects(Arrays::getValues($selects))
            ->addJoins($joins)
            ->addCounts()
            ->toSQL($query);

        return Framework::getDatabase()->interpolateQuery($expression, $query->getParams());
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
        return Query::truncate(static::$tableName)->execute() > 0;
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
        return ModificationBuilder::insert(static::getModel())
            ->addFields($request, $fields)
            ->addCreation($credentialID)
            ->addModification()
            ->execute();
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
        return ModificationBuilder::replace(static::getModel())
            ->addFields($request, $fields)
            ->addModification($credentialID)
            ->execute();
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
        $query = self::generateQueryID($query, withDeleted: false);
        return ModificationBuilder::update(static::getModel(), $query)
            ->addFields($request, $fields, $skipEmpty, $skipUnset)
            ->addModification($credentialID, $skipTimestamps)
            ->execute() > 0;
    }

    /**
     * Deletes the Data of an Entity
     * @param Query|int|string $query
     * @param int              $credentialID Optional.
     * @return bool
     */
    protected static function deleteSchemaEntity(Query|int|string $query, int $credentialID = 0): bool {
        $query = self::generateQueryID($query, withDeleted: false);
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
        $query = self::generateQueryID($query, withDeleted: false);
        return Query::delete(static::$tableName)->query($query)->execute() > 0;
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
        $modification = ModificationBuilder::insert(static::getModel())
            ->addFields($request, $fields)
            ->addCreation($credentialID)
            ->addModification();

        $newPosition = self::ensureSchemaOrder(null, $modification->getFields(), $orderQuery);
        $modification->setField("position", $newPosition);
        return $modification->execute();
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
        $modifyQuery  = self::generateQueryID($query, withDeleted: false);
        $modification = ModificationBuilder::update(static::getModel(), $modifyQuery)
            ->addFields($request, $fields, $skipEmpty, $skipUnset)
            ->addModification($credentialID, $skipTimestamps);

        $fields = $modification->getFields();
        if ($fields->has("position")) {
            $elem         = self::getSchemaEntity($query);
            $savePosition = self::ensureSchemaOrder($elem, $fields, $orderQuery);
            $modification->setField("position", $savePosition);
        }

        return $modification->execute() > 0;
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
     * @param Dictionary|null $oldFields
     * @param Dictionary|null $newFields
     * @param Query|null      $query       Optional.
     * @param int             $minPosition Optional.
     * @return int
     */
    protected static function ensureSchemaOrder(
        ?Dictionary $oldFields,
        ?Dictionary $newFields,
        ?Query $query = null,
        int $minPosition = 0,
    ): int {
        $isCreate     = Arrays::isEmpty($oldFields)  && !Arrays::isEmpty($newFields);
        $isEdit       = !Arrays::isEmpty($oldFields) && !Arrays::isEmpty($newFields);
        $isDelete     = !Arrays::isEmpty($oldFields) && Arrays::isEmpty($newFields);

        $oldPosition  = $oldFields !== null ? $oldFields->getInt("position") : 0;
        $newPosition  = $newFields !== null ? $newFields->getInt("position") : 0;
        $nextPosition = self::getNextPosition($query);

        $oldPosition  = $isCreate ? $nextPosition : $oldPosition;
        $newPosition  = $newPosition > 0 ? $newPosition : $nextPosition - ($isEdit ? 1 : 0);
        $savePosition = $newPosition;

        // In a create if the position is the last one, we just set the element to the last position
        if ($isCreate && ($newPosition === 0 || $newPosition >= $nextPosition)) {
            return $nextPosition;
        }

        // If this is an edit and the position didn't change, we don't need to do anything
        if (!$isDelete && $oldPosition === $newPosition) {
            return $newPosition;
        }

        // Apply the minimum position
        if ($newPosition < $minPosition) {
            $newPosition = $minPosition;
        }

        // On an edit if the position is the last one, set it to 1 lower
        if ($isEdit && $newPosition > $nextPosition) {
            $savePosition = $nextPosition - 1;
        }

        $builder = Query::update(static::$tableName)
            ->query(self::generateQuery($query));

        if ($newPosition > $oldPosition) {
            $builder->set("position", Assign::decrease(1));
            $builder->where("position", Operator::GreaterThan, $oldPosition);
            $builder->where("position", Operator::LessOrEqual, $newPosition);
        } else {
            $builder->set("position", Assign::increase(1));
            $builder->where("position", Operator::GreaterOrEqual, $newPosition);
            $builder->where("position", Operator::LessThan, $oldPosition);
        }

        $builder->execute();
        return $savePosition;
    }

    /**
     * Gets the Next Position
     * @param Query|null $query Optional.
     * @return int
     */
    private static function getNextPosition(?Query $query = null): int {
        $query = self::generateQuery($query);
        $query->orderBy("position", isASC: false);
        $query->limit(1);

        $request = SelectionBuilder::create(static::getModel())
            ->addSelects("position", addMainKey: true)
            ->addJoins(withSelects: false)
            ->request($query)
            ->getResult();

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
        if ($newValue !== 0 && $oldValue === 0) {
            $query->where(static::$idDbName, Operator::NotEqual, $id);
            $query->where($column, Operator::Equal, 1);
            self::editSchemaEntity($query, null, [ $column => 0 ]);
            return true;
        }
        if ($newValue === 0 && $oldValue !== 0) {
            $query = self::generateQuery($query, withDeleted: true);
            $query->limit(1);
            self::editSchemaEntity($query, null, [ $column => 1 ]);
            return true;
        }
        return false;
    }



    /**
     * Generates a Query with the ID or returns the Query
     * @param Query|int|string $queryOrID
     * @param bool             $withDeleted Optional.
     * @return Query
     */
    private static function generateQueryID(Query|int|string $queryOrID, bool $withDeleted = true): Query {
        if ($queryOrID instanceof Query) {
            $query = $queryOrID;
        } else {
            $query = new Query();
            $query->where(static::$idDbName, Operator::Equal, $queryOrID);
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
            $query->orderBy(static::$idDbName, isASC: true);
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
        $query     = (new Query())->query($query);
        $isDeleted = static::getModel()->getKey("isDeleted");

        if ($withDeleted && static::$canDelete &&
            !$query->hasWhereColumn($isDeleted) &&
            !$query->hasWhereColumn("isDeleted")
        ) {
            $query->where($isDeleted, Operator::Equal, 0);
        }
        return $query;
    }
}
