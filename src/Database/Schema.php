<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Request;
use Framework\Database\Database;
use Framework\Database\Structure;
use Framework\Database\Selection;
use Framework\Database\Modification;
use Framework\Database\Assign;
use Framework\Database\Query;
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

    protected const Schema = "";


    /**
     * Returns the Database
     * @return Database
     */
    private static function db(): Database {
        return Framework::getDatabase();
    }

    /**
     * Returns the Structure
     * @return Structure
     */
    private static function structure(): Structure {
        $schemaName = Strings::toString(static::Schema);
        return Factory::getStructure($schemaName);
    }



    /**
     * Returns true if a Table exists
     * @return boolean
     */
    public static function tableExists(): bool {
        return self::db()->tableExists(self::structure()->table);
    }

    /**
     * Returns true if the Schema has a Primary Key
     * @return boolean
     */
    public static function hasPrimaryKey(): bool {
        $keys = self::db()->getPrimaryKeys(self::structure()->table);
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
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @param boolean              $decrypted   Optional.
     * @return array{}
     */
    protected static function getSchemaEntity(Query|int|string $query, bool $withDeleted = true, bool $decrypted = false): array {
        $query   = self::generateQueryID($query, $withDeleted)->limit(1);
        $request = self::requestSchemaData($query, decrypted: $decrypted);
        return isset($request[0]) ? $request[0] : [];
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query  $query
     * @param string $column
     * @return string|integer
     */
    protected static function getSchemaValue(Query $query, string $column): string|int {
        return self::db()->getValue(self::structure()->table, $column, $query);
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query|null $query
     * @param string     $column
     * @param string     $columnKey Optional.
     * @return array<string|integer>
     */
    protected static function getSchemaColumn(?Query $query, string $column, string $columnKey = ""): array {
        $query     = self::generateQuery($query);
        $selection = new Selection(self::structure());
        $selection->addFields();
        $selection->addSelects($column, true);
        $selection->addJoins();

        $selection->request($query);
        $request = $selection->resolve();
        $result  = [];

        $columnKey = $columnKey !== "" ? $columnKey : Strings::substringAfter($column, ".");
        foreach ($request as $row) {
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
     * @param boolean    $withDeleted Optional.
     * @return integer
     */
    protected static function getSchemaTotal(?Query $query = null, bool $withDeleted = true): int {
        $query     = self::generateQuery($query, $withDeleted);
        $selection = new Selection(self::structure());
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
     * @param Query|null           $query          Optional.
     * @param string|null          $idColumn       Optional.
     * @param string[]|string|null $nameColumn     Optional.
     * @param string[]|string|null $extraColumn    Optional.
     * @param string|null          $distinctColumn Optional.
     * @param boolean              $useEmpty       Optional.
     * @return Select[]
     */
    protected static function getSchemaSelect(
        ?Query $query = null,
        ?string $idColumn = null,
        array|string|null $nameColumn = null,
        array|string|null $extraColumn = null,
        ?string $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        $query     = self::generateQuery($query);
        $selection = new Selection(self::structure());
        if ($distinctColumn !== null) {
            $selection->addSelects("DISTINCT($distinctColumn)");
        }

        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->request($query);
        $request = $selection->resolve();

        $keyName = $idColumn ?? self::structure()->idName;
        $valName = $nameColumn !== null && !Arrays::isEmpty($nameColumn) ? $nameColumn : self::structure()->nameKey;
        return Select::create($request, $keyName, $valName, $useEmpty, $extraColumn, true);
    }

    /**
     * Returns the Search results
     * @param Query                $query
     * @param string|null          $idColumn   Optional.
     * @param string[]|string|null $nameColumn Optional.
     * @param integer              $limit      Optional.
     * @return Search[]
     */
    protected static function getSchemaSearch(
        Query $query,
        ?string $idColumn = null,
        array|string|null $nameColumn = null,
        int $limit = 0,
    ): array {
        $query   = self::generateQuery($query)->limit($limit);
        $request = self::requestSchemaData($query);

        if ($idColumn === null || $idColumn === "") {
            $idColumn = self::structure()->idName;
        }
        if ($nameColumn === null || Arrays::isEmpty($nameColumn)) {
            $nameColumn = self::structure()->nameKey;
        }
        return Search::create($request, $idColumn, $nameColumn);
    }



    /**
     * Returns the Data using a basic Expression and Params
     * @param string  $expression
     * @param mixed[] $params     Optional.
     * @return array<string,string|integer>[]
     */
    protected static function getDataWithParams(string $expression, array $params = []): array {
        $expression = self::structure()->replaceTable($expression);
        $request    = self::db()->queryData($expression, $params);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return array<string,string|integer>[]
     */
    protected static function getSchemaData(Query $query, string $expression): array {
        $expression = self::structure()->replaceTable($expression);
        $request    = self::db()->getData($expression, $query);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query  $query
     * @param string $expression
     * @return array<string,string|integer>
     */
    protected static function getSchemaRow(Query $query, string $expression): array {
        $expression = self::structure()->replaceTable($expression);
        $request    = self::db()->getData($expression, $query);

        if (isset($request[0])) {
            return $request[0];
        }
        return [];
    }

    /**
     * Returns an array of Entities Data
     * @param Query|null   $query     Optional.
     * @param Request|null $sort      Optional.
     * @param array{}      $selects   Optional.
     * @param string[]     $joins     Optional.
     * @param boolean      $decrypted Optional.
     * @return array{}[]
     */
    protected static function getSchemaEntities(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
    ): array {
        $query   = self::generateQuerySort($query, $sort);
        $request = self::requestSchemaData($query, $selects, $joins, $decrypted);
        return $request;
    }

    /**
     * Requests data to the database
     * @param Query                $query
     * @param array<string,string> $selects   Optional.
     * @param string[]             $joins     Optional.
     * @param boolean              $decrypted Optional.
     * @return array{}[]
     */
    private static function requestSchemaData(Query $query, array $selects = [], array $joins = [], bool $decrypted = false): array {
        $selection = new Selection(self::structure());
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();
        $selection->request($query);

        $result = $selection->resolve(array_keys($selects));
        foreach (self::structure()->subRequests as $subRequest) {
            $result = $subRequest->request($result);
        }
        return $result;
    }



    /**
     * Returns the expression of the Query
     * @param Query|null           $query     Optional.
     * @param Request|null         $sort      Optional.
     * @param array<string,string> $selects   Optional.
     * @param string[]             $joins     Optional.
     * @param boolean              $decrypted Optional.
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
        $selection = new Selection(self::structure());
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();

        $expression = $selection->getExpression($query);
        return self::db()->interpolateQuery($expression, $query);
    }

    /**
     * Returns the expression of the data Query
     * @param Query  $query
     * @param string $expression
     * @return string
     */
    protected static function getDataExpression(Query $query, string $expression): string {
        $expression  = self::structure()->replaceTable($expression);
        $expression .= $query->get();
        return self::db()->interpolateQuery($expression, $query);
    }



    /**
     * Truncates all the Entities
     * @return boolean
     */
    protected static function truncateData(): bool {
        return self::db()->truncate(self::structure()->table);
    }

    /**
     * Creates a new Entity with data
     * @param Request|null        $request      Optional.
     * @param array<string,mixed> $fields       Optional.
     * @param integer             $credentialID Optional.
     * @return integer
     */
    protected static function createSchemaEntity(?Request $request = null, array $fields = [], int $credentialID = 0): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();
        return $modification->insert();
    }

    /**
     * Replaces the Data of an Entity
     * @param Request|null        $request      Optional.
     * @param array<string,mixed> $fields       Optional.
     * @param integer             $credentialID Optional.
     * @return integer
     */
    protected static function replaceSchemaEntity(?Request $request = null, array $fields = [], int $credentialID = 0): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addModification($credentialID);
        return $modification->replace();
    }

    /**
     * Edits the Data of an Entity
     * @param Query|integer|string $query
     * @param Request|null         $request      Optional.
     * @param array<string,mixed>  $fields       Optional.
     * @param integer              $credentialID Optional.
     * @param boolean              $skipEmpty    Optional.
     * @param boolean              $skipUnset    Optional.
     * @return boolean
     */
    protected static function editSchemaEntity(
        Query|int|string $query,
        ?Request $request = null,
        array $fields = [],
        int $credentialID = 0,
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): bool {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields, $skipEmpty, $skipUnset);
        $modification->addModification($credentialID);

        $query = self::generateQueryID($query, false);
        return $modification->update($query);
    }

    /**
     * Deletes the Data of an Entity
     * @param Query|integer|string $query
     * @param integer              $credentialID Optional.
     * @return boolean
     */
    protected static function deleteSchemaEntity(Query|int|string $query, int $credentialID = 0): bool {
        $query = self::generateQueryID($query, false);
        if (self::structure()->canDelete) {
            return self::editSchemaEntity($query, null, [ "isDeleted" => 1 ], $credentialID);
        }
        return false;
    }

    /**
     * Removes the Data of an Entity
     * @param Query|integer|string $query
     * @return boolean
     */
    protected static function removeSchemaEntity(Query|int|string $query): bool {
        $query = self::generateQueryID($query, false);
        return self::db()->delete(self::structure()->table, $query);
    }



    /**
     * Creates an Entity and ensures the Order
     * @param Request|null        $request
     * @param array<string,mixed> $fields       Optional.
     * @param integer             $credentialID Optional.
     * @param Query|null          $orderQuery   Optional.
     * @return integer
     */
    protected static function createSchemaEntityWithOrder(?Request $request, array $fields = [], int $credentialID = 0, ?Query $orderQuery = null): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();

        $newPosition = self::ensureSchemaOrder(null, $modification->getFields(), $orderQuery);
        $modification->setField("position", $newPosition);
        return $modification->insert();
    }

    /**
     * Edits the Data of an Entity and ensures the Order
     * @param Query|integer|string $query
     * @param Request|null         $request
     * @param array<string,mixed>  $fields       Optional.
     * @param integer              $credentialID Optional.
     * @param Query|null           $orderQuery   Optional.
     * @param boolean              $skipEmpty    Optional.
     * @param boolean              $skipUnset    Optional.
     * @return boolean
     */
    protected static function editSchemaEntityWithOrder(
        Query|int|string $query,
        ?Request $request,
        array $fields = [],
        int $credentialID = 0,
        ?Query $orderQuery = null,
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): bool {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields, $skipEmpty, $skipUnset);
        $modification->addModification($credentialID);

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
     * @param Query|integer|string $query
     * @param integer              $credentialID Optional.
     * @param Query|null           $orderQuery   Optional.
     * @return boolean
     */
    protected static function deleteSchemaEntityWithOrder(Query|int|string $query, int $credentialID = 0, ?Query $orderQuery = null): bool {
        $elem = self::getSchemaEntity($query);
        if (self::deleteSchemaEntity($query, $credentialID)) {
            self::ensureSchemaOrder($elem, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Removes the Data of an Entity and ensures the Order
     * @param Query|integer|string $query
     * @param Query|null           $orderQuery Optional.
     * @return boolean
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
     * @return integer
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
            $newQuery->add("position", ">",  $oldPosition);
            $newQuery->add("position", "<=", $newPosition);
            $assign = Assign::decrease(1);
        } else {
            $newQuery->add("position", ">=", $newPosition);
            $newQuery->add("position", "<",  $oldPosition);
            $assign = Assign::increase(1);
        }

        self::db()->update(self::structure()->table, [
            "position" => $assign,
        ], $newQuery);
        return $savePosition;
    }

    /**
     * Gets the Next Position
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return integer
     */
    private static function getNextPosition(?Query $query = null, bool $withDeleted = true): int {
        if (!self::structure()->hasPositions) {
            return 0;
        }

        $selection = new Selection(self::structure());
        $selection->addSelects("position", true);
        $selection->addJoins(withSelects: false);

        $query = self::generateQuery($query, $withDeleted);
        $query->orderBy("position", false);
        $query->limit(1);

        $request = $selection->request($query);
        if (isset($request[0])) {
            return Numbers::toInt($request[0]["position"]) + 1;
        }
        return 1;
    }

    /**
     * Ensures that only one Entity has the Unique column set
     * @param string     $column
     * @param integer    $id
     * @param integer    $oldValue
     * @param integer    $newValue
     * @param Query|null $query    Optional.
     * @return boolean
     */
    protected static function ensureSchemaUniqueData(
        string $column,
        int $id,
        int $oldValue,
        int $newValue,
        ?Query $query = null,
    ): bool {
        $updated = false;
        if ($newValue !== 0 && $oldValue === 0) {
            $newQuery = new Query($query);
            $newQuery->add(self::structure()->idKey, "<>", $id);
            $newQuery->add($column, "=", 1);
            self::editSchemaEntity($newQuery, null, [ $column => 0 ]);
            $updated = true;
        }
        if ($newValue === 0 && $oldValue !== 0) {
            $newQuery = self::generateQuery($query, true);
            $newQuery->orderBy(self::structure()->getOrder(), true);
            $newQuery->limit(1);
            self::editSchemaEntity($newQuery, null, [ $column => 1 ]);
            $updated = true;
        }
        return $updated;
    }



    /**
     * Generates a Query with the ID or returns the Query
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @return Query
     */
    private static function generateQueryID(Query|int|string $query, bool $withDeleted = true): Query {
        if (!($query instanceof Query)) {
            $query = Query::create(self::structure()->idKey, "=", $query);
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
        } elseif (!$query->hasOrder() && self::structure()->hasID) {
            $query->orderBy(self::structure()->idKey, true);
        }
        return $query;
    }

    /**
     * Generates a Query without Deleted if required
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return Query
     */
    private static function generateQuery(?Query $query = null, bool $withDeleted = true): Query {
        $query     = new Query($query);
        $isDeleted = self::structure()->getKey("isDeleted");

        if ($withDeleted && self::structure()->canDelete && !$query->hasColumn($isDeleted) && !$query->hasColumn("isDeleted")) {
            $query->add($isDeleted, "=", 0);
        }
        return $query;
    }
}
