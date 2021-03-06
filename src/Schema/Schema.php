<?php
namespace Framework\Schema;

use Framework\Request;
use Framework\Schema\Database;
use Framework\Schema\Selection;
use Framework\Schema\Modification;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Schema
 */
class Schema {

    private $db;
    private $structure;
    private $subrequests;


    /**
     * Creates a new Schema instance
     * @param Database  $db
     * @param Structure $structure
     * @param array     $subrequests Optional.
     */
    public function __construct(Database $db, Structure $structure, array $subrequests = []) {
        $this->db          = $db;
        $this->structure   = $structure;
        $this->subrequests = $subrequests;
    }

    /**
     * Returns the Schema Fields
     * @return array
     */
    public function getFields(): array {
        return $this->structure->fields;
    }

    /**
     * Encrypts the given Value
     * @param string $value
     * @return array
     */
    public function encrypt(string $value): array {
        return Query::encrypt($value, $this->structure->masterKey);
    }



    /**
     * Returns the Model with the given Where
     * @param Query|integer $query
     * @param boolean       $withDeleted Optional.
     * @param boolean       $decrypted   Optional.
     * @return Model
     */
    public function getOne($query, bool $withDeleted = true, bool $decrypted = false): Model {
        $query   = $this->generateQueryID($query, $withDeleted)->limit(1);
        $request = $this->request($query, $decrypted);
        return $this->getModel($request);
    }

    /**
     * Creates a new Model using the given Data
     * @param array $request Optional.
     * @return Model
     */
    public function getModel(array $request = null): Model {
        if (!empty($request[0])) {
            return new Model($this->structure->idName, $request[0]);
        }
        return new Model($this->structure->idName);
    }

    /**
     * Returns true if there is a Schema with the given ID
     * @param Query|integer $query
     * @param boolean       $withDeleted Optional.
     * @return boolean
     */
    public function exists($query, bool $withDeleted = true): bool {
        $query = $this->generateQueryID($query, $withDeleted);
        return $this->getTotal($query) == 1;
    }



    /**
     * Returns the first line of the given query
     * @param string $expression
     * @param array  $params     Optional.
     * @return array
     */
    public function getQuery(string $expression, array $params = []): array {
        $expression = Strings::replace($expression, "{table}", "{dbPrefix}{$this->structure->table}");
        $request    = $this->db->query($expression, $params);
        return $request;
    }

    /**
     * Returns an array of Schemas
     * @param Query   $query     Optional.
     * @param Request $sort      Optional.
     * @param boolean $decrypted Optional.
     * @return array
     */
    public function getAll(Query $query = null, Request $sort = null, bool $decrypted = false): array {
        $query   = $this->generateQuerySort($query, $sort);
        $request = $this->request($query, $decrypted);
        return $request;
    }

    /**
     * Returns a map of Schemas
     * @param Query   $query     Optional.
     * @param Request $sort      Optional.
     * @param boolean $decrypted Optional.
     * @return array
     */
    public function getMap(Query $query = null, Request $sort = null, bool $decrypted = false): array {
        $query   = $this->generateQuerySort($query, $sort);
        $request = $this->request($query, $decrypted);
        return Arrays::createMap($request, $this->structure->idName);
    }

    /**
     * Returns the expression of the Query
     * @param Query   $query     Optional.
     * @param Request $sort      Optional.
     * @param boolean $decrypted Optional.
     * @return array
     */
    public function getExpression(Query $query, Request $sort = null, bool $decrypted = false) {
        $query     = $this->generateQuerySort($query, $sort);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decrypted);
        $selection->addJoins();
        $selection->addCounts();
        $expression = $selection->getExpression($query);
        return $this->db->interpolateQuery($expression, $query);
    }

    /**
     * Requests data to the database
     * @param Query   $query     Optional.
     * @param boolean $decrypted Optional.
     * @return array
     */
    private function request(Query $query = null, bool $decrypted = false): array {
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decrypted);
        $selection->addJoins();
        $selection->addCounts();
        $selection->request($query);

        $result = $selection->resolve();
        foreach ($this->subrequests as $subrequest) {
            $result = $subrequest->request($result);
        }
        return $result;
    }



    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query   $query
     * @param array   $selects
     * @param boolean $decrypted Optional.
     * @param boolean $withSubs  Optional.
     * @return array
     */
    public function getColumns(Query $query, array $selects, bool $decrypted = false, bool $withSubs = false): array {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decrypted);
        $selection->addSelects(array_values($selects));
        $selection->addJoins();
        $selection->request($query);

        $result = $selection->resolve(array_keys($selects));
        if ($withSubs) {
            foreach ($this->subrequests as $subrequest) {
                $result = $subrequest->request($result);
            }
        }
        return $result;
    }

    /**
     * Selects the given Data
     * @param Query    $query
     * @param string[] $selects
     * @param boolean  $withFields Optional.
     * @return array
     */
    public function getSome(Query $query, array $selects, bool $withFields = false): array {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects($selects);
        $selection->addJoins();
        $selection->addCounts();
        if ($withFields) {
            $selection->addFields();
        }
        $selection->request($query);
        return $selection->resolve();
    }

    /**
     * Gets a Total using the Joins
     * @param Query   $query       Optional.
     * @param string  $column      Optional.
     * @param boolean $withDeleted Optional.
     * @return integer
     */
    public function getTotal(Query $query = null, string $column = "*", bool $withDeleted = true): int {
        $query     = $this->generateQuery($query, $withDeleted);
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects("COUNT($column) AS cnt");
        $selection->addJoins();

        $request = $selection->request($query);
        if (isset($request[0]["cnt"])) {
            return (int)$request[0]["cnt"];
        }
        return 0;
    }

    /**
     * Gets a Sum using the Joins
     * @param Query  $query
     * @param string $column
     * @return integer
     */
    public function getSum(Query $query, string $column): int {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects("SUM($column) AS cnt");
        $selection->addJoins();

        $request = $selection->request($query);
        if (isset($request[0]["cnt"])) {
            return (int)$request[0]["cnt"];
        }
        return 0;
    }

    /**
     * Returns the first line of the given query
     * @param Query  $query
     * @param string $select
     * @return array
     */
    public function getStats(Query $query, string $select): array {
        $select  = Strings::replace($select, "{table}", "{dbPrefix}{$this->structure->table}");
        $request = $this->db->query("$select " . $query->get(), $query);

        if (!empty($request[0])) {
            return $request[0];
        }
        return [];
    }

    /**
     * Returns the search results
     * @param Query           $query
     * @param string|string[] $name  Optional.
     * @return array
     */
    public function getSearch(Query $query, $name = null): array {
        $query   = $this->generateQuery($query);
        $request = $this->request($query);
        $result  = [];

        foreach ($request as $row) {
            $result[] = [
                "id"    => $row[$this->structure->idName],
                "title" => Arrays::getValue($row, $name ?: $this->structure->name),
                "data"  => $row,
            ];
        }
        return $result;
    }

    /**
     * Selects the given column from a single table and returns the entire column
     * @param Query  $query
     * @param string $column
     * @return string[]
     */
    public function getColumn(Query $query, string $column): array {
        $columnName = Strings::substringAfter($column, ".");
        $query      = $this->generateQuery($query);
        $selection  = new Selection($this->db, $this->structure);
        $selection->addSelects($column, true);
        $selection->addJoins();

        $request = $selection->request($query);
        $result  = [];
        foreach ($request as $row) {
            if (!empty($row[$columnName]) && !Arrays::contains($result, $row[$columnName])) {
                $result[] = $row[$columnName];
            }
        }
        return $result;
    }

    /**
     * Gets the Next Position
     * @param Query   $query       Optional.
     * @param boolean $withDeleted Optional.
     * @return integer
     */
    public function getNextPosition(Query $query = null, bool $withDeleted = true): int {
        if (!$this->structure->hasPositions) {
            return 0;
        }
        $selection = new Selection($this->db, $this->structure);
        $selection->addSelects("position", true);
        $selection->addJoins(false);

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
     * Selects the given column from a single table and returns a single value
     * @param Query  $query
     * @param string $column
     * @return mixed
     */
    public function getValue(Query $query, string $column) {
        return $this->db->getValue($this->structure->table, $column, $query);
    }



    /**
     * Returns all the Sorted Names
     * @param string          $order    Optional.
     * @param boolean         $orderAsc Optional.
     * @param string|string[] $name     Optional.
     * @param boolean         $useEmpty Optional.
     * @param string          $extra    Optional.
     * @return array
     */
    public function getSortedNames(string $order = null, bool $orderAsc = true, $name = null, bool $useEmpty = false, string $extra = null): array {
        $field = $this->structure->getOrder($order);
        $query = Query::createOrderBy($field, $orderAsc);
        return $this->getSelect($query, $name, $useEmpty, $extra);
    }

    /**
     * Returns all the Sorted Names using the given Query
     * @param Query           $query
     * @param string          $order    Optional.
     * @param boolean         $orderAsc Optional.
     * @param string|string[] $name     Optional.
     * @param boolean         $useEmpty Optional.
     * @param string          $extra    Optional.
     * @return array
     */
    public function getSortedSelect(Query $query, string $order = null, bool $orderAsc = true, $name = null, bool $useEmpty = false, string $extra = null): array {
        $field = $this->structure->getOrder($order);
        $query->orderBy($field, $orderAsc);
        return $this->getSelect($query, $name, $useEmpty, $extra);
    }

    /**
     * Returns a select of Schemas
     * @param Query           $query
     * @param string|string[] $name     Optional.
     * @param boolean         $useEmpty Optional.
     * @param string          $extra    Optional.
     * @return array
     */
    public function getSelect(Query $query, $name = null, bool $useEmpty = false, string $extra = null): array {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields();
        $selection->addJoins();
        $selection->request($query);
        $request   = $selection->resolve();
        return Arrays::createSelect($request, $this->structure->idName, $name ?: $this->structure->name, $useEmpty, $extra);
    }



    /**
     * Creates a new Schema
     * @param Request|array $fields
     * @param array|integer $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return integer
     */
    public function create($fields, $extra = null, int $credentialID = 0): int {
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID);
        $modification->addCreation();
        $modification->addModification();
        return $modification->insert();
    }

    /**
     * Replaces the Schema
     * @param Request|array $fields
     * @param array|integer $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return integer
     */
    public function replace($fields, $extra = null, int $credentialID = 0): int {
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID);
        $modification->addModification();
        return $modification->replace();
    }

    /**
     * Edits the Schema
     * @param Query|integer $query
     * @param Request|array $fields
     * @param array|integer $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public function edit($query, $fields, $extra = null, int $credentialID = 0): bool {
        $query        = $this->generateQueryID($query, false);
        $modification = new Modification($this->db, $this->structure);
        $modification->addFields($fields, $extra, $credentialID);
        $modification->addModification();
        return $modification->update($query);
    }



    /**
     * Updates a single value increasing it by the given amount
     * @param Query|integer $query
     * @param string        $column
     * @param integer       $amount
     * @return boolean
     */
    public function increase($query, string $column, int $amount): bool {
        $query = $this->generateQueryID($query, false);
        return $this->db->increase($this->structure->table, $column, $amount, $query);
    }

    /**
     * Batches the Schema
     * @param array $fields
     * @return boolean
     */
    public function batch(array $fields): bool {
        return $this->db->batch($this->structure->table, $fields);
    }

    /**
     * Deletes the given Schema
     * @param Query|integer $query
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public function delete($query, int $credentialID = 0): bool {
        $query = $this->generateQueryID($query, false);
        if ($this->structure->canDelete && $this->exists($query)) {
            $this->edit($query, [ "isDeleted" => 1 ], $credentialID);
            return true;
        }
        return false;
    }

    /**
     * Removes the given Schema
     * @param Query|integer $query
     * @return boolean
     */
    public function remove($query): bool {
        $query = $this->generateQueryID($query, false);
        return $this->db->delete($this->structure->table, $query);
    }

    /**
     * Truncates the given Schema
     * @return boolean
     */
    public function truncate() {
        return $this->db->truncate($this->structure->table);
    }



    /**
     * Creates and ensures the Order
     * @param Request $request
     * @param array   $extra        Optional.
     * @param integer $credentialID Optional.
     * @return integer
     */
    public function createWithOrder(Request $request, array $extra = null, int $credentialID = 0): int {
        $this->ensurePosOrder(null, $request);
        return $this->create($request, $extra, $credentialID);
    }

    /**
     * Edits and ensures the Order
     * @param Query|integer $query
     * @param Request       $request
     * @param array         $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public function editWithOrder($query, Request $request, array $extra = null, int $credentialID = 0): bool {
        $model = $this->getOne($query);
        $this->ensurePosOrder($model, $request);
        return $this->edit($query, $request, $extra, $credentialID);
    }

    /**
     * Deletes and ensures the Order
     * @param Query|integer $query
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public function deleteWithOrder($query, int $credentialID = 0): bool {
        $model = $this->getOne($query);
        if ($this->delete($query, $credentialID)) {
            $this->ensurePosOrder($model, null);
            return true;
        }
        return false;
    }

    /**
     * Ensures that the order of the Elements is correct
     * @param Model   $model   Optional.
     * @param Request $request Optional.
     * @return void
     */
    public function ensurePosOrder(Model $model = null, Request $request = null): void {
        $oldPosition = !empty($model)   ? $model->position             : 0;
        $newPosition = !empty($request) ? $request->getInt("position") : 0;
        $updPosition = $this->ensureOrder($oldPosition, $newPosition);
        if (!empty($request)) {
            $request->position = $updPosition;
        }
    }

    /**
     * Ensures that the order of the Elements is correct on Create/Edit
     * @param integer $oldPosition
     * @param integer $newPosition
     * @param Query   $query       Optional.
     * @return integer
     */
    public function ensureOrder(int $oldPosition, int $newPosition, Query $query = null): int {
        $isEdit          = !empty($oldPosition);
        $nextPosition    = $this->getNextPosition($query);
        $oldPosition     = $isEdit ? $oldPosition : $nextPosition;
        $newPosition     = !empty($newPosition) ? $newPosition : $nextPosition;
        $updatedPosition = $newPosition;

        if (!$isEdit && (empty($newPosition) || $newPosition > $nextPosition)) {
            return $nextPosition;
        }
        if ($oldPosition == $newPosition) {
            return $newPosition;
        }

        if ($isEdit && $newPosition > $nextPosition) {
            $updatedPosition = $nextPosition - 1;
        }
        if ($newPosition > $oldPosition) {
            $newQuery = new Query($query);
            $newQuery->add("position",  ">",  $oldPosition);
            $newQuery->add("position",  "<=", $newPosition);
            $newQuery->add("isDeleted", "=",  0);
            $this->increase($newQuery, "position", -1);
        } else {
            $newQuery = new Query($query);
            $newQuery->add("position",  ">=", $newPosition);
            $newQuery->add("position",  "<",  $oldPosition);
            $newQuery->add("isDeleted", "=",  0);
            $this->increase($newQuery, "position", 1);
        }
        return $updatedPosition;
    }

    /**
     * Ensures that only one Element has the Unique field set
     * @param string  $field
     * @param integer $id
     * @param integer $oldValue
     * @param integer $newValue
     * @param Query   $query    Optional.
     * @return void
     */
    public function ensureUnique(string $field, int $id, int $oldValue, int $newValue, Query $query = null): void {
        if (!empty($newValue) && empty($oldValue)) {
            $newQuery = new Query($query);
            $newQuery->add($this->structure->idKey, "<>", $id);
            $newQuery->add($field, "=", 1);
            $this->edit($newQuery, [ $field => 0 ]);
        }
        if (empty($newValue) && !empty($oldValue)) {
            $newQuery = new Query($query);
            $newQuery->orderBy($this->structure->getOrder(), true)->limit(1);
            $this->edit($newQuery, [ $field => 1 ]);
        }
    }



    /**
     * Generates a Query
     * @param Query   $query       Optional.
     * @param boolean $withDeleted Optional.
     * @return Query
     */
    private function generateQuery(Query $query = null, bool $withDeleted = true): Query {
        $query     = new Query($query);
        $isDeleted = $this->structure->getKey("isDeleted");

        if ($withDeleted && $this->structure->canDelete && !$query->hasColumn($isDeleted)) {
            $query->add($isDeleted, "=", 0);
        }
        return $query;
    }

    /**
     * Generates a Query with the ID or returns the Query
     * @param Query|integer $query
     * @param boolean       $withDeleted Optional.
     * @return Query
     */
    private function generateQueryID($query, bool $withDeleted = true): Query {
        if (!($query instanceof Query)) {
            $query = Query::create($this->structure->idKey, "=", $query);
        }
        return $this->generateQuery($query, $withDeleted);
    }

    /**
     * Generates a Query with Sorting
     * @param Query   $query Optional.
     * @param Request $sort  Optional.
     * @return Query
     */
    private function generateQuerySort(Query $query = null, Request $sort = null): Query {
        $query = $this->generateQuery($query);

        if (!empty($sort)) {
            if ($sort->has("orderBy")) {
                $query->orderBy($sort->orderBy, !empty($sort->orderAsc));
            }
            if ($sort->exists("page")) {
                $query->paginate($sort->page, $sort->amount);
            }
        } elseif (!$query->hasOrder() && !empty($this->structure->idKey)) {
            $query->orderBy($this->structure->idKey, true);
        }
        return $query;
    }
}
