<?php
namespace Framework\Schema;

use Framework\Schema\Database;
use Framework\Schema\Selection;
use Framework\Schema\Modification;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use Framework\Request;

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
    public function getFields() {
        return $this->structure->fields;
    }

    /**
     * Encrypts the given Value
     * @param string $value
     * @return string
     */
    public function encrypt($value) {
        return Query::encrypt($value, $this->structure->masterKey);
    }
    
    
    
    /**
     * Returns the Model with the given Where
     * @param Query|integer $query
     * @param boolean       $withDeleted Optional.
     * @param boolean       $decripted   Optional.
     * @return Model
     */
    public function getOne($query, $withDeleted = true, $decripted = false) {
        $query   = $this->generateQueryID($query, $withDeleted)->limit(1);
        $request = $this->request($query, $decripted);
        return $this->getModel($request);
    }
    
    /**
     * Creates a new Model using the given Data
     * @param array $request Optional.
     * @return Model
     */
    public function getModel(array $request = null) {
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
    public function exists($query, $withDeleted = true) {
        $query = $this->generateQueryID($query, $withDeleted);
        return $this->getTotal($query) == 1;
    }



    /**
     * Returns the first line of the given query
     * @param string $expression
     * @param array  $params     Optional.
     * @return array
     */
    public function getQuery($expression, array $params = []) {
        $expression = Strings::replace($expression, "{table}", "{dbPrefix}{$this->structure->table}");
        $request    = $this->db->query($expression, $params);
        return $request;
    }

    /**
     * Returns an array of Schemas
     * @param Query   $query     Optional.
     * @param Request $sort      Optional.
     * @param string  $decripted Optional.
     * @return array
     */
    public function getAll(Query $query = null, Request $sort = null, $decripted = false) {
        $query   = $this->generateQuerySort($query, $sort);
        $request = $this->request($query, $decripted);
        return $request;
    }

    /**
     * Returns a map of Schemas
     * @param Query   $query     Optional.
     * @param Request $sort      Optional.
     * @param string  $decripted Optional.
     * @return array
     */
    public function getMap(Query $query = null, Request $sort = null, $decripted = false) {
        $query   = $this->generateQuerySort($query, $sort);
        $request = $this->request($query, $decripted);
        return Arrays::createMap($request, $this->structure->idName);
    }
    
    /**
     * Requests data to the database
     * @param Query  $query     Optional.
     * @param string $decripted Optional.
     * @return array
     */
    private function request(Query $query = null, $decripted = false) {
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields($decripted);
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
     * @param Query           $query
     * @param string|string[] $columns
     * @param string|string[] $names
     * @return array
     */
    public function getColumns(Query $query, $columns, $names) {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields();
        $selection->addSelects($columns);
        $selection->addJoins();
        $selection->request($query);
        return $selection->resolve($names);
    }

    /**
     * Selects the given Data
     * @param Query    $query
     * @param string[] $selects
     * @param boolean  $withFields Optional.
     * @return array
     */
    public function getSome(Query $query, array $selects, $withFields = false) {
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
    public function getTotal(Query $query = null, $column = "*", $withDeleted = true) {
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
    public function getSum(Query $query, $column) {
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
    public function getStats(Query $query, $select) {
        $select  = Strings::replace($select, "{table}", "{dbPrefix}{$this->structure->table}");
        $request = $this->db->query("$select " . $query->get(), $query);

        if (!empty($request[0])) {
            return $request[0];
        }
        return [];
    }

    /**
     * Returns the search results
     * @param Query  $query
     * @param string $title Optional.
     * @return array
     */
    public function getSearch(Query $query, $title = null) {
        $query   = $this->generateQuery($query);
        $request = $this->request($query);
        $result  = [];
        
        foreach ($request as $row) {
            $key = $title ?: $this->structure->name;
            $result[] = [
                "id"    => $row[$this->structure->idName],
                "title" => $row[$key],
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
    public function getColumn(Query $query, $column) {
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
    public function getNextPosition(Query $query = null, $withDeleted = true) {
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
     * @return string
     */
    public function getValue(Query $query, $column) {
        return $this->db->getValue($this->structure->table, $column, $query);
    }
    


    /**
     * Returns all the Sorted Names
     * @param string  $order    Optional.
     * @param boolean $orderAsc Optional.
     * @param string  $name     Optional.
     * @return array
     */
    public function getSortedNames($order = null, $orderAsc = true, $name = null) {
        $field = $order ?: ($this->structure->hasPositions ? "position" : $this->structure->name);
        $query = Query::createOrderBy($field, $orderAsc);
        return $this->getSelect($query, $name);
    }
    
    /**
     * Returns all the Sorted Names using the given Query
     * @param Query   $query
     * @param string  $order    Optional.
     * @param boolean $orderAsc Optional.
     * @param string  $name     Optional.
     * @return array
     */
    public function getSortedSelect(Query $query, $order = null, $orderAsc = true, $name = null) {
        $field = $order ?: ($this->structure->hasPositions ? "position" : $this->structure->name);
        $query->orderBy($field, $orderAsc);
        return $this->getSelect($query, $name);
    }
    
    /**
     * Returns a select of Schemas
     * @param Query  $query
     * @param string $name  Optional.
     * @return array
     */
    public function getSelect(Query $query, $name = null) {
        $query     = $this->generateQuery($query);
        $selection = new Selection($this->db, $this->structure);
        $selection->addFields();
        $selection->addJoins();
        $selection->request($query);
        $request   = $selection->resolve();
        return Arrays::createSelect($request, $this->structure->idName, $name ?: $this->structure->name);
    }
    
    
    
    

    /**
     * Creates a new Schema
     * @param Request|array $fields
     * @param array|integer $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return integer
     */
    public function create($fields, $extra = null, $credentialID = 0) {
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
    public function replace($fields, $extra = null, $credentialID = 0) {
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
    public function edit($query, $fields, $extra = null, $credentialID = 0) {
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
     * @return void
     */
    public function increase($query, $column, $amount) {
        $query = $this->generateQueryID($query, false);
        $this->db->increase($this->structure->table, $column, $amount, $query);
    }
    
    /**
     * Batches the Schema
     * @param array $fields
     * @return void
     */
    public function batch(array $fields) {
        $this->db->batch($this->structure->table, $fields);
    }
    
    /**
     * Deletes the given Schema
     * @param Query|integer $query
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public function delete($query, $credentialID = 0) {
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
    public function remove($query) {
        $query = $this->generateQueryID($query, false);
        return $this->db->delete($this->structure->table, $query);
    }



    /**
     * Generates a Query
     * @param Query   $query       Optional.
     * @param boolean $withDeleted Optional.
     * @return Query
     */
    private function generateQuery(Query $query = null, $withDeleted = true) {
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
    private function generateQueryID($query, $withDeleted = true) {
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
    private function generateQuerySort(Query $query = null, Request $sort = null) {
        $query = $this->generateQuery($query);
        
        if (!empty($sort) && $sort->has("orderBy")) {
            $query->orderBy($sort->orderBy, !empty($sort->orderAsc));
            if ($sort->exists("page")) {
                $query->paginate($sort->page, $sort->amount);
            }
        } elseif (!$query->hasOrder() && !empty($this->structure->idKey)) {
            $query->orderBy($this->structure->idKey, true);
        }
        return $query;
    }
}
