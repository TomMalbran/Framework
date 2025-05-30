<?php
namespace Framework\Database;

use Framework\Database\Structure;
use Framework\Database\Query;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;

/**
 * The Schema SubRequests
 */
class SubRequest {

    private Structure $structure;

    public  string $type     = "";
    public  string $name     = "";

    private string $idKey    = "";
    private string $idName   = "";

    /** @var string[] */
    private array  $where    = [];

    private bool   $hasOrder = false;
    private string $orderBy  = "";
    private bool   $isAsc    = false;

    private bool   $asArray  = false;
    private string $field    = "";
    private string $value    = "";

    /** @var string[] */
    private array  $values   = [];


    /**
     * Creates a new SubRequest instance
     * @param Structure  $structure
     * @param Dictionary $data
     * @param string     $idKey
     * @param string     $idName
     */
    public function __construct(Structure $structure, Dictionary $data, string $idKey, string $idName) {
        $this->structure = $structure;

        $this->name      = $data->getString("name");
        $this->type      = $data->getString("type", $structure->schema);
        $this->idKey     = $data->getString("idKey", $idKey);
        $this->idName    = $data->getString("idName", $idName);
        $this->where     = $data->getStrings("where");

        $this->hasOrder  = $data->hasValue("orderBy");
        $this->orderBy   = $data->getString("orderBy");
        $this->isAsc     = $data->hasValue("isAsc");

        $this->asArray   = $data->hasValue("asArray");
        $this->field     = $data->getString("field");
        $this->value     = $data->getString("value");
        $this->values    = $data->getStrings("value");
    }



    /**
     * Does the Request with a Sub Request
     * @param array<string,mixed>[] $result
     * @return array<string,mixed>[]
     */
    public function request(array $result): array {
        $query     = $this->createQuery($result);
        $request   = $this->getData($query);
        $subResult = [];

        foreach ($request as $row) {
            if (!isset($row[$this->idName])) {
                continue;
            }

            $name = $row[$this->idName];
            if (!isset($subResult[$name])) {
                $subResult[$name] = [];
            }

            if ($this->field === "") {
                $subResult[$name][] = $this->getValues($row);
                continue;
            }

            $field = $row[$this->field];
            if (!$this->asArray) {
                $subResult[$name][$field] = $this->getValues($row);
                continue;
            }

            if (!isset($subResult[$name][$field])) {
                $subResult[$name][$field] = [];
            }
            if (is_array($subResult[$name][$field])) {
                $subResult[$name][$field][] = $this->getValues($row);
            }
        }

        foreach ($result as $index => $row) {
            $result[$index][$this->name] = [];
            foreach ($subResult as $key => $subRow) {
                if ($row[$this->idName] === $key) {
                    $result[$index][$this->name] = $subRow;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Data from the Query
     * @param Query|null $query
     * @return array<string,mixed>[]
     */
    private function getData(?Query $query): array {
        if ($query === null) {
            return [];
        }

        $selection = new Selection($this->structure);
        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->addCounts();
        $selection->request($query);
        return $selection->resolve();
    }

    /**
     * Does the Request with a Sub Request
     * @param mixed[] $result
     * @return Query|null
     */
    private function createQuery(array $result): ?Query {
        $ids = Arrays::createArray($result, $this->idName);
        if (count($ids) === 0) {
            return null;
        }
        $query = Query::create($this->idKey, "IN", $ids);

        $total = count($this->where);
        if ($total % 3 === 0) {
            for ($i = 0; $i < $total; $i += 3) {
                $query->add($this->where[$i], $this->where[$i + 1], $this->where[$i + 2]);
            }
        }

        if ($this->hasOrder) {
            $query->orderBy($this->orderBy, $this->isAsc);
        }

        if ($this->structure->canDelete) {
            $isDeleted = $this->structure->getKey("isDeleted");
            $query->add($isDeleted, "=", 0);
        }
        return $query;
    }

    /**
     * Returns the Values depending on the Data
     * @param array<string,mixed> $row
     * @return mixed
     */
    private function getValues(array $row): mixed {
        if ($this->value !== "") {
            return $row[$this->value];
        }
        if (count($this->values) > 0) {
            $result = [];
            foreach ($this->values as $value) {
                $result[$value] = $row[$value];
            }
            return $result;
        }
        return $row;
    }
}
