<?php
namespace Framework\Schema;

use Framework\Request;
use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\Schema\SubRequest;
use Framework\Schema\Selection;
use Framework\Schema\Modification;
use Framework\Schema\Field;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\Search;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use ArrayAccess;

/**
 * The Schema
 */
class Schema {

    private Database  $db;
    private Structure $structure;

    /** @var SubRequest[] */
    private array     $subRequests;


    /**
     * Creates a new Schema instance
     * @param Database     $db
     * @param Structure    $structure
     * @param SubRequest[] $subRequests Optional.
     */
    public function __construct(Database $db, Structure $structure, array $subRequests = []) {
        $this->db          = $db;
        $this->structure   = $structure;
        $this->subRequests = $subRequests;
    }

    /**
     * Returns the Schema Fields
     * @return Field[]
     */
    public function getFields(): array {
        return $this->structure->fields;
    }

    /**
     * Returns the Schema Master Key
     * @return string
     */
    public function getMasterKey(): string {
        return $this->structure->masterKey;
    }

    /**
     * Encrypts the given Value
     * @param string $value
     * @return array{}
     */
    public function encrypt(string $value): array {
        return Query::encrypt($value, $this->structure->masterKey);
    }

    /**
     * Returns true if a Table exists
     * @return boolean
     */
    public function tableExists(): bool {
        return $this->db->tableExists($this->structure->table);
    }

    /**
     * Returns true if the Schema has a Primary Key
     * @return boolean
     */
    public function hasPrimaryKey(): bool {
        $keys = $this->db->getPrimaryKeys($this->structure->table);
        return !empty($keys);
    }



    /**
     * Returns the Model with the given ID or Query
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @param boolean              $decrypted   Optional.
     * @return Model
     */
    public function getOne(Query|int|string $query, bool $withDeleted = true, bool $decrypted = false): Model {
        $query   = $this->generateQueryID($query, $withDeleted)->limit(1);
        $request = $this->request($query, decrypted: $decrypted);
        return $this->getModel($request);
    }

    /**
     * Creates a new Model using the given Data
     * @param array{}|null $request Optional.
     * @return Model
     */
    public function getModel(?array $request = null): Model {
        if (!empty($request[0])) {
            return new Model($this->structure->idName, $request[0]);
        }
        return new Model($this->structure->idName);
    }

    /**
     * Returns the Row with the given ID or Query
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @param boolean              $decrypted   Optional.
     * @return array{}
     */
    public function getRow(Query|int|string $query, bool $withDeleted = true, bool $decrypted = false): array {
        $query   = $this->generateQueryID($query, $withDeleted)->limit(1);
        $request = $this->request($query, decrypted: $decrypted);
        return !empty($request[0]) ? $request[0] : [];
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param Query|integer|string $query
     * @param string               $column
     * @return mixed
     */
    public function getValue(Query|int|string $query, string $column): mixed {
        $query = $this->generateQueryID($query, false)->limit(1);
        return $this->db->getValue($this->structure->table, $column, $query);
    }

    /**
     * Returns true if there is a Schema with the given ID
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @return boolean
     */
    public function exists(Query|int|string $query, bool $withDeleted = true): bool {
        $query = $this->generateQueryID($query, $withDeleted);
        return $this->getTotal($query) > 0;
    }



    /**
     * Process the given expression
     * @param string  $expression
     * @param array{} $params     Optional.
     * @return array{}[]
     */
    public function getQuery(string $expression, array $params = []): array {
        $expression = $this->structure->replaceTable($expression);
        $request    = $this->db->query($expression, $params);
        return $request;
    }

    /**
     * Process the given expression using a Query
     * @param Query  $query
     * @param string $expression
     * @return array{}[]
     */
    public function getData(Query $query, string $expression): array {
        $expression = $this->structure->replaceTable($expression);
        $request    = $this->db->getData($expression, $query);
        return $request;
    }

    /**
     * Returns an array of Schemas
     * @param Query|null   $query     Optional.
     * @param Request|null $sort      Optional.
     * @param array{}      $selects   Optional.
     * @param string[]     $joins     Optional.
     * @param boolean      $decrypted Optional.
     * @return array{}[]
     */
    public function getAll(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
    ): array {
        $query   = $this->generateQuerySort($query, $sort);
        $request = $this->request($query, $selects, $joins, $decrypted);
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
    private function request(?Query $query = null, array $selects = [], array $joins = [], bool $decrypted = false): array {
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();
        $selection->request($query);

        $result = $selection->resolve(array_keys($selects));
        foreach ($this->subRequests as $subRequest) {
            $result = $subRequest->request($result);
        }
        return $result;
    }



    /**
     * Gets a Total using the Joins
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return integer
     */
    public function getTotal(?Query $query = null, bool $withDeleted = true): int {
        $query     = $this->generateQuery($query, $withDeleted);
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects("COUNT(*) AS cnt");
        $selection->addJoins(withSelects: false);

        $request = $selection->request($query);
        if (isset($request[0]["cnt"])) {
            return (int)$request[0]["cnt"];
        }
        return 0;
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query|null $query
     * @param string     $column
     * @param string     $columnKey Optional.
     * @return string[]
     */
    public function getColumn(?Query $query, string $column, string $columnKey = ""): array {
        $columnKey = empty($columnKey) ? Strings::substringAfter($column, ".") : $columnKey;

        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects($column, true);
        $selection->addJoins();

        $request = $selection->request($query);
        $result  = [];
        foreach ($request as $row) {
            if (!empty($row[$columnKey]) && !Arrays::contains($result, $row[$columnKey])) {
                $result[] = $row[$columnKey];
            }
        }
        return $result;
    }

    /**
     * Returns the first line of the given query
     * @param Query  $query
     * @param string $expression
     * @return array{}
     */
    public function getStats(Query $query, string $expression): array {
        $expression = $this->structure->replaceTable($expression);
        $request    = $this->db->query("$expression " . $query->get(), $query);

        if (!empty($request[0])) {
            return $request[0];
        }
        return [];
    }

    /**
     * Returns the Search results
     * @param Query                $query
     * @param string[]|string|null $name   Optional.
     * @param string|null          $idName Optional.
     * @param integer              $limit  Optional.
     * @return Search[]
     */
    public function getSearch(Query $query, array|string $name = null, ?string $idName = null, int $limit = 0): array {
        $query   = $this->generateQuery($query)->limitIf($limit);
        $request = $this->request($query);
        $idKey   = $idName ?: $this->structure->idName;
        $nameKey = $name   ?: $this->structure->nameKey;
        return Search::create($request, $idKey, $nameKey);
    }

    /**
     * Returns a Select array
     * @param Query|null           $query      Optional.
     * @param string|null          $orderKey   Optional.
     * @param boolean              $orderAsc   Optional.
     * @param string|null          $idName     Optional.
     * @param string[]|string|null $nameKey    Optional.
     * @param string[]|string|null $extraKey   Optional.
     * @param boolean              $useEmpty   Optional.
     * @param string|null          $distinctID Optional.
     * @return Select[]
     */
    public function getSelect(
        ?Query $query = null,
        ?string $orderKey = null,
        bool $orderAsc = true,
        ?string $idName = null,
        array|string $nameKey = null,
        array|string $extraKey = null,
        bool $useEmpty = false,
        ?string $distinctID = null
    ): array {
        $query = $this->generateQuery($query);
        if (!$query->hasOrder()) {
            $field = $this->structure->getOrder($orderKey);
            $query->orderBy($field, $orderAsc);
        }

        $selection = new Selection($this->db, $this->structure);
        if ($distinctID !== null) {
            $selection->addSelects("DISTINCT($distinctID)");
        }

        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->request($query);
        $request = $selection->resolve();

        $keyName = $idName  ?: $this->structure->idName;
        $valName = $nameKey ?: $this->structure->nameKey;
        return Select::create($request, $keyName, $valName, $useEmpty, $extraKey, true);
    }

    /**
     * Gets the Next Position
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return integer
     */
    public function getNextPosition(?Query $query = null, bool $withDeleted = true): int {
        if (!$this->structure->hasPositions) {
            return 0;
        }

        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects("position", true);
        $selection->addJoins(withSelects: false);

        $query = $this->generateQuery($query, $withDeleted);
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
    public function getExpression(
        ?Query $query = null,
        ?Request $sort = null,
        array $selects = [],
        array $joins = [],
        bool $decrypted = false,
    ): string {
        $query     = $this->generateQuerySort($query, $sort);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decrypted);
        $selection->addExpressions();
        $selection->addSelects(array_values($selects));
        $selection->addJoins($joins);
        $selection->addCounts();

        $expression = $selection->getExpression($query);
        return $this->db->interpolateQuery($expression, $query);
    }

    /**
     * Returns the expression of the data Query
     * @param Query  $query
     * @param string $expression
     * @return string
     */
    public function getDataExpression(Query $query, string $expression): string {
        $expression  = $this->structure->replaceTable($expression);
        $expression .= $query->get();
        return $this->db->interpolateQuery($expression, $query);
    }



    /**
     * Creates a new Schema
     * @param Request|array{}      $fields
     * @param array{}|integer|null $extra        Optional.
     * @param integer              $credentialID Optional.
     * @return integer
     */
    public function create(Request|array $fields, array|int $extra = null, int $credentialID = 0): int {
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID);
        $modification->addCreation();
        $modification->addModification();
        return $modification->insert();
    }

    /**
     * Replaces the Schema
     * @param Request|array{}      $fields
     * @param array{}|integer|null $extra        Optional.
     * @param integer              $credentialID Optional.
     * @return integer
     */
    public function replace(Request|array $fields, array|int $extra = null, int $credentialID = 0): int {
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID);
        $modification->addModification();
        return $modification->replace();
    }

    /**
     * Edits the Schema
     * @param Query|integer|string $query
     * @param Request|array{}      $fields
     * @param array{}|integer|null $extra        Optional.
     * @param integer              $credentialID Optional.
     * @param boolean              $skipEmpty    Optional.
     * @return boolean
     */
    public function edit(Query|int|string $query, Request|array $fields, array|int $extra = null, int $credentialID = 0, bool $skipEmpty = false): bool {
        $query        = $this->generateQueryID($query, false);
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID, $skipEmpty);
        $modification->addModification();
        return $modification->update($query);
    }



    /**
     * Updates a single value increasing it by the given amount
     * @param Query|integer|string $query
     * @param string               $column
     * @param integer              $amount
     * @return boolean
     */
    public function increase(Query|int|string $query, string $column, int $amount): bool {
        $query = $this->generateQueryID($query, false);
        return $this->db->increase($this->structure->table, $column, $amount, $query);
    }

    /**
     * Batches the Schema
     * @param array{}[] $fields
     * @return boolean
     */
    public function batch(array $fields): bool {
        return $this->db->batch($this->structure->table, $fields);
    }

    /**
     * Deletes the given Schema
     * @param Query|integer|string $query
     * @param integer              $credentialID Optional.
     * @return boolean
     */
    public function delete(Query|int|string $query, int $credentialID = 0): bool {
        $query = $this->generateQueryID($query, false);
        if ($this->structure->canDelete && $this->exists($query)) {
            $this->edit($query, [ "isDeleted" => 1 ], $credentialID);
            return true;
        }
        return false;
    }

    /**
     * Removes the given Schema
     * @param Query|integer|string $query
     * @return boolean
     */
    public function remove(Query|int|string $query): bool {
        $query = $this->generateQueryID($query, false);
        return $this->db->delete($this->structure->table, $query);
    }

    /**
     * Truncates the given Schema
     * @return boolean
     */
    public function truncate(): bool {
        return $this->db->truncate($this->structure->table);
    }



    /**
     * Creates and ensures the Order
     * @param Request|array{} $fields
     * @param array{}|null    $extra        Optional.
     * @param integer         $credentialID Optional.
     * @param Query|null      $orderQuery   Optional.
     * @return integer
     */
    public function createWithOrder(Request|array $fields, ?array $extra = null, int $credentialID = 0, ?Query $orderQuery = null): int {
        if (!empty($extra["position"])) {
            $fields["position"] = $extra["position"];
        }
        $fields["position"] = $this->ensurePosOrder(null, $fields, $orderQuery);
        return $this->create($fields, $extra, $credentialID);
    }

    /**
     * Edits and ensures the Order
     * @param Query|integer|string $query
     * @param Request|array{}      $fields
     * @param array{}|integer|null $extra        Optional.
     * @param integer              $credentialID Optional.
     * @param Query|null           $orderQuery   Optional.
     * @return boolean
     */
    public function editWithOrder(Query|int|string $query, Request|array $fields, array|int $extra = null, int $credentialID = 0, ?Query $orderQuery = null): bool {
        $model = $this->getOne($query);
        $fields["position"] = $this->ensurePosOrder($model, $fields, $orderQuery);
        return $this->edit($query, $fields, $extra, $credentialID);
    }

    /**
     * Deletes and ensures the Order
     * @param Query|integer|string $query
     * @param integer              $credentialID Optional.
     * @param Query|null           $orderQuery   Optional.
     * @return boolean
     */
    public function deleteWithOrder(Query|int|string $query, int $credentialID = 0, ?Query $orderQuery = null): bool {
        $model = $this->getOne($query);
        if ($this->delete($query, $credentialID)) {
            $this->ensurePosOrder($model, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Removes and ensures the Order
     * @param Query|integer|string $query
     * @param Query|null           $orderQuery Optional.
     * @return boolean
     */
    public function removeWithOrder(Query|int|string $query, ?Query $orderQuery = null): bool {
        $model = $this->getOne($query);
        if ($this->remove($query)) {
            $this->ensurePosOrder($model, null, $orderQuery);
            return true;
        }
        return false;
    }

    /**
     * Edits all the Positions
     * @param Request $request
     * @param integer $credentialID Optional.
     * @return boolean
     */
    public function editPositions(Request $request, int $credentialID = 0): bool {
        $result = true;
        foreach ($request->getArray("ids") as $index => $id) {
            if (!$this->edit($id, [ "position" => $index + 1 ], $credentialID)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Ensures that the order of the Elements is correct
     * @param ArrayAccess|array{}|null $oldFields
     * @param ArrayAccess|array{}|null $newFields
     * @param Query|null               $query     Optional.
     * @return integer
     */
    public function ensurePosOrder(ArrayAccess|array|null $oldFields, ArrayAccess|array|null $newFields, ?Query $query = null): int {
        $oldPosition = !empty($oldFields["position"]) ? (int)$oldFields["position"] : 0;
        $newPosition = !empty($newFields["position"]) ? (int)$newFields["position"] : 0;
        $updPosition = $this->ensureOrder($oldPosition, $newPosition, $query);
        return $updPosition;
    }

    /**
     * Ensures that the order of the Elements is correct on Create/Edit
     * @param integer    $oldPosition
     * @param integer    $newPosition
     * @param Query|null $query       Optional.
     * @return integer
     */
    public function ensureOrder(int $oldPosition, int $newPosition, ?Query $query = null): int {
        $isEdit          = !empty($oldPosition);
        $nextPosition    = $this->getNextPosition($query);
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
        if ($newPosition > $oldPosition) {
            $newQuery = $this->generateQuery($query);
            $newQuery->add("position",  ">",  $oldPosition);
            $newQuery->add("position",  "<=", $newPosition);
            $this->increase($newQuery, "position", -1);
        } else {
            $newQuery = $this->generateQuery($query);
            $newQuery->add("position",  ">=", $newPosition);
            $newQuery->add("position",  "<",  $oldPosition);
            $this->increase($newQuery, "position", 1);
        }
        return $updatedPosition;
    }

    /**
     * Ensures that only one Element has the Unique field set
     * @param string     $field
     * @param integer    $id
     * @param integer    $oldValue
     * @param integer    $newValue
     * @param Query|null $query    Optional.
     * @return boolean
     */
    public function ensureUnique(string $field, int $id, int $oldValue, int $newValue, ?Query $query = null): bool {
        $updated = false;
        if (!empty($newValue) && empty($oldValue)) {
            $newQuery = new Query($query);
            $newQuery->add($this->structure->idKey, "<>", $id);
            $newQuery->add($field, "=", 1);
            $this->edit($newQuery, [ $field => 0 ]);
            $updated = true;
        }
        if (empty($newValue) && !empty($oldValue)) {
            $newQuery = $this->generateQuery($query, true);
            $newQuery->orderBy($this->structure->getOrder(), true);
            $newQuery->limit(1);
            $this->edit($newQuery, [ $field => 1 ]);
            $updated = true;
        }
        return $updated;
    }



    /**
     * Generates a Query
     * @param Query|null $query       Optional.
     * @param boolean    $withDeleted Optional.
     * @return Query
     */
    private function generateQuery(?Query $query = null, bool $withDeleted = true): Query {
        $query     = new Query($query);
        $isDeleted = $this->structure->getKey("isDeleted");

        if ($withDeleted && $this->structure->canDelete && !$query->hasColumn($isDeleted) && !$query->hasColumn("isDeleted")) {
            $query->add($isDeleted, "=", 0);
        }
        return $query;
    }

    /**
     * Generates a Query with the ID or returns the Query
     * @param Query|integer|string $query
     * @param boolean              $withDeleted Optional.
     * @return Query
     */
    private function generateQueryID(Query|int|string $query, bool $withDeleted = true): Query {
        if (!($query instanceof Query)) {
            $query = Query::create($this->structure->idKey, "=", $query);
        }
        return $this->generateQuery($query, $withDeleted);
    }

    /**
     * Generates a Query with Sorting
     * @param Query|null   $query Optional.
     * @param Request|null $sort  Optional.
     * @return Query
     */
    private function generateQuerySort(?Query $query = null, ?Request $sort = null): Query {
        $query = $this->generateQuery($query);

        if (!empty($sort)) {
            if ($sort->has("orderBy")) {
                $query->orderBy($sort->orderBy, !empty($sort->orderAsc));
            }
            if ($sort->exists("page")) {
                $query->paginate($sort->getInt("page"), $sort->getInt("amount"));
            }
        } elseif (!$query->hasOrder() && $this->structure->hasID) {
            $query->orderBy($this->structure->idKey, true);
        }
        return $query;
    }
}
