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
use Framework\Utils\Arrays;
use Framework\Utils\Search;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use ArrayAccess;

/**
 * The Schema
 */
class Schema {

    const Name = "";

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
        return Factory::getStructure(static::Name);
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
        return !empty($keys);
    }

    /**
     * Returns the Structure Master Key
     * @return string
     */
    protected static function getMasterKey(): string {
        return self::structure()->masterKey;
    }

    /**
     * Encrypts the given Value
     * @param string $value
     * @return Assign
     */
    protected static function encrypt(string $value): Assign {
        return Assign::encrypt($value, self::getMasterKey());
    }



    /**
     * Returns the Data of an Entity with the given ID or Query
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @param boolean              $decrypted   Optional.
     * @return array{}
     */
    protected static function getEntityData(Query|int|string $query, bool $withDeleted = true, bool $decrypted = false): array {
        $query   = self::generateQueryID($query, $withDeleted)->limit(1);
        $request = self::requestData($query, decrypted: $decrypted);
        return !empty($request[0]) ? $request[0] : [];
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query  $query
     * @param string $column
     * @return mixed
     */
    protected static function getValueData(Query $query, string $column): mixed {
        return self::db()->getValue(self::structure()->table, $column, $query);
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query|null $query
     * @param string     $column
     * @param string     $columnKey Optional.
     * @return string[]
     */
    protected static function getColumnData(?Query $query, string $column, string $columnKey = ""): array {
        $query     = self::generateQuery($query);
        $selection = new Selection(self::structure());
        $selection->addSelects($column, true);
        $selection->addJoins();

        $columnKey = !empty($columnKey) ? $columnKey : Strings::substringAfter($column, ".");
        $request   = $selection->request($query);
        $result    = [];

        foreach ($request as $row) {
            if (!empty($row[$columnKey]) && !Arrays::contains($result, $row[$columnKey])) {
                $result[] = $row[$columnKey];
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
    protected static function getEntityTotal(?Query $query = null, bool $withDeleted = true): int {
        $query     = self::generateQuery($query, $withDeleted);
        $selection = new Selection(self::structure());
        $selection->addSelects("COUNT(*) AS cnt");
        $selection->addJoins(withSelects: false);

        $request = $selection->request($query);
        if (isset($request[0]["cnt"])) {
            return (int)$request[0]["cnt"];
        }
        return 0;
    }

    /**
     * Returns a Select array
     * @param Query|null           $query       Optional.
     * @param string|null          $orderColumn Optional.
     * @param boolean              $orderAsc    Optional.
     * @param string|null          $idColumn    Optional.
     * @param string[]|string|null $nameColumn  Optional.
     * @param string[]|string|null $extraColumn Optional.
     * @param boolean              $useEmpty    Optional.
     * @param string|null          $distinctID  Optional.
     * @return Select[]
     */
    protected static function getSelectData(
        ?Query $query = null,
        ?string $orderColumn = null,
        bool $orderAsc = true,
        ?string $idColumn = null,
        array|string|null $nameColumn = null,
        array|string|null $extraColumn = null,
        ?string $distinctColumn = null,
        bool $useEmpty = false,
    ): array {
        $query = self::generateQuery($query);
        if (!$query->hasOrder()) {
            $field = self::structure()->getOrder($orderColumn);
            $query->orderBy($field, $orderAsc);
        }

        $selection = new Selection(self::structure());
        if ($distinctColumn !== null) {
            $selection->addSelects("DISTINCT($distinctColumn)");
        }

        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->request($query);
        $request = $selection->resolve();

        $keyName = $idColumn ?: self::structure()->idName;
        $valName = !empty($nameColumn) ? $nameColumn : self::structure()->nameKey;
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
    public static function getSearchData(
        Query $query,
        ?string $idColumn = null,
        array|string|null $nameColumn = null,
        int $limit = 0,
    ): array {
        $query   = self::generateQuery($query)->limitIf($limit);
        $request = self::requestData($query);
        $idKey   = !empty($idColumn)   ? $idColumn   : self::structure()->idName;
        $nameKey = !empty($nameColumn) ? $nameColumn : self::structure()->nameKey;
        return Search::create($request, $idKey, $nameKey);
    }



    /**
     * Returns the Data using a basic Expression and Params
     * @param string  $expression
     * @param array{} $params     Optional.
     * @return array{}[]
     */
    protected static function getDataWithParams(string $expression, array $params = []): array {
        $expression = self::structure()->replaceTable($expression);
        $request    = self::db()->query($expression, $params);
        return $request;
    }

    /**
     * Returns the Data using a basic Expression and a Query
     * @param Query   $query
     * @param string  $expression
     * @param boolean $singleLine Optional.
     * @return array{}
     */
    protected static function getDataWithQuery(Query $query, string $expression, bool $singleLine = false): array {
        $expression = self::structure()->replaceTable($expression);
        $request    = self::db()->getData($expression, $query);

        if ($singleLine && !empty($request[0])) {
            return $request[0];
        }
        return $request;
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
    protected static function getEntitiesData(?Query $query = null, ?Request $sort = null, array $selects = [], array $joins = [], bool $decrypted = false): array {
        $query   = self::generateQuerySort($query, $sort);
        $request = self::requestData($query, $selects, $joins, $decrypted);
        return $request;
    }

    /**
     * Requests data to the database
     * @param Query|null $query     Optional.
     * @param array{}    $selects   Optional.
     * @param string[]   $joins     Optional.
     * @param boolean    $decrypted Optional.
     * @return array{}[]
     */
    private static function requestData(?Query $query = null, array $selects = [], array $joins = [], bool $decrypted = false): array {
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
     * Gets the Next Position
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return integer
     */
    protected static function getNextPosition(?Query $query = null, bool $withDeleted = true): int {
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
        if (!empty($request[0])) {
            return (int)$request[0]["position"] + 1;
        }
        return 1;
    }

    /**
     * Returns the expression of the Query
     * @param Query|null   $query     Optional.
     * @param Request|null $sort      Optional.
     * @param array{}      $selects   Optional.
     * @param string[]     $joins     Optional.
     * @param boolean      $decrypted Optional.
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
     * Batches the Entity Data
     * @param array{}[] $fields
     * @return boolean
     */
    protected static function batchEntities(array $fields): bool {
        if (empty($fields)) {
            return false;
        }
        return self::db()->batch(self::structure()->table, $fields);
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
     * @param Request|null $request      Optional.
     * @param array{}      $fields       Optional.
     * @param integer      $credentialID Optional.
     * @return integer
     */
    protected static function createEntityData(?Request $request = null, array $fields = [], int $credentialID = 0): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();
        return $modification->insert();
    }

    /**
     * Replaces the Data of an Entity
     * @param Request|null $request      Optional.
     * @param array{}      $fields       Optional.
     * @param integer      $credentialID Optional.
     * @return integer
     */
    protected static function replaceEntityData(?Request $request = null, array $fields = [], int $credentialID = 0): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addModification($credentialID);
        return $modification->replace();
    }

    /**
     * Edits the Data of an Entity
     * @param Query|integer|string $query
     * @param Request|null         $request      Optional.
     * @param array{}              $fields       Optional.
     * @param integer              $credentialID Optional.
     * @param boolean              $skipEmpty    Optional.
     * @return boolean
     */
    protected static function editEntityData(Query|int|string $query, ?Request $request = null, array $fields = [], int $credentialID = 0, bool $skipEmpty = false): bool {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields, $skipEmpty);
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
    protected static function deleteEntityData(Query|int|string $query, int $credentialID = 0): bool {
        $query = self::generateQueryID($query, false);
        if (self::structure()->canDelete) {
            return self::editEntityData($query, null, [ "isDeleted" => 1 ], $credentialID);
        }
        return false;
    }

    /**
     * Removes the Data of an Entity
     * @param Query|integer|string $query
     * @return boolean
     */
    protected static function removeEntityData(Query|int|string $query): bool {
        $query = self::generateQueryID($query, false);
        return self::db()->delete(self::structure()->table, $query);
    }



    /**
     * Creates an Entity and ensures the Order
     * @param Request|null $request
     * @param array{}      $fields       Optional.
     * @param integer      $credentialID Optional.
     * @param Query|null   $orderQuery   Optional.
     * @return integer
     */
    protected static function createEntityWithOrder(?Request $request, array $fields = [], int $credentialID = 0, ?Query $orderQuery = null): int {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addCreation($credentialID);
        $modification->addModification();

        $newPosition = self::ensureEntityOrder(null, $modification->getFields(), $orderQuery);
        $modification->setField("position", $newPosition);
        return $modification->insert();
    }

    /**
     * Edits the Data of an Entity and ensures the Order
     * @param Query|integer|string $query
     * @param Request|null         $request
     * @param array{}              $fields       Optional.
     * @param integer              $credentialID Optional.
     * @param Query|null           $orderQuery   Optional.
     * @return boolean
     */
    protected static function editEntityWithOrder(Query|int|string $query, ?Request $request, array $fields = [], int $credentialID = 0, ?Query $orderQuery = null): bool {
        $modification = new Modification(self::structure());
        $modification->addFields($request, $fields);
        $modification->addModification($credentialID);

        $fields = $modification->getFields();
        if (isset($fields["position"])) {
            $elem        = self::getEntityData($query);
            $newPosition = self::ensureEntityOrder($elem, $fields, $orderQuery);
            $modification->setField("position", $newPosition);
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
    protected static function deleteEntityWithOrder(Query|int|string $query, int $credentialID = 0, ?Query $orderQuery = null): bool {
        $elem = self::getEntityData($query);
        if (self::deleteEntityData($query, $credentialID)) {
            self::ensureEntityOrder($elem, null, $orderQuery);
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
    protected static function removeEntityWithOrder(Query|int|string $query, ?Query $orderQuery = null): bool {
        $elem = self::getEntityData($query);
        if (self::removeEntityData($query)) {
            self::ensureEntityOrder($elem, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Ensures that the Order of the Entities is correct
     * @param ArrayAccess|array{}|null $oldFields
     * @param ArrayAccess|array{}|null $newFields
     * @param Query|null               $query     Optional.
     * @return integer
     */
    protected static function ensureEntityOrder(ArrayAccess|array|null $oldFields, ArrayAccess|array|null $newFields, ?Query $query = null): int {
        $oldPosition = !empty($oldFields["position"]) ? (int)$oldFields["position"] : 0;
        $newPosition = !empty($newFields["position"]) ? (int)$newFields["position"] : 0;
        $updPosition = self::ensureOrder($oldPosition, $newPosition, $query);
        return $updPosition;
    }

    /**
     * Ensures that the Order of the Entities is correct on Create/Edit
     * @param integer    $oldPosition
     * @param integer    $newPosition
     * @param Query|null $query       Optional.
     * @return integer
     */
    protected static function ensureOrder(int $oldPosition, int $newPosition, ?Query $query = null): int {
        $isEdit          = !empty($oldPosition);
        $nextPosition    = self::getNextPosition($query);
        $oldPosition     = $isEdit ? $oldPosition : $nextPosition;
        $newPosition     = !empty($newPosition) ? $newPosition : $nextPosition;
        $updatedPosition = $newPosition;

        if (!$isEdit && (empty($newPosition) || $newPosition >= $nextPosition)) {
            return $nextPosition;
        }
        if ($oldPosition == $newPosition) {
            return $newPosition;
        }

        if ($isEdit && $newPosition > $nextPosition) {
            $updatedPosition = $nextPosition - 1;
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
        return $updatedPosition;
    }

    /**
     * Ensures that only one Entity has the Unique field set
     * @param string     $field
     * @param integer    $id
     * @param integer    $oldValue
     * @param integer    $newValue
     * @param Query|null $query    Optional.
     * @return boolean
     */
    protected static function ensureUniqueData(string $field, int $id, int $oldValue, int $newValue, ?Query $query = null): bool {
        $updated = false;
        if (!empty($newValue) && empty($oldValue)) {
            $newQuery = new Query($query);
            $newQuery->add(self::structure()->idKey, "<>", $id);
            $newQuery->add($field, "=", 1);
            self::editEntityData($newQuery, null, [ $field => 0 ]);
            $updated = true;
        }
        if (empty($newValue) && !empty($oldValue)) {
            $newQuery = self::generateQuery($query, true);
            $newQuery->orderBy(self::structure()->getOrder(), true);
            $newQuery->limit(1);
            self::editEntityData($newQuery, null, [ $field => 1 ]);
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

        if (!empty($sort)) {
            if ($sort->has("orderBy")) {
                $query->orderBy($sort->orderBy, !empty($sort->orderAsc));
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
