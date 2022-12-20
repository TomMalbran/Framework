<?php
namespace Framework\Schema;

use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\Query;
use Framework\Utils\Arrays;

/**
 * The Schema Subrequests
 */
class Subrequest {

    private Schema $schema;

    private string $name     = "";
    private string $idKey    = "";
    private string $idName   = "";

    private bool   $hasWhere = false;

    /** @var mixed[] */
    private array  $where    = [];

    private bool   $hasOrder = false;
    private string $orderBy  = "";
    private bool   $isAsc    = false;

    private string $field    = "";
    private mixed  $value    = null;


    /**
     * Creates a new Subrequest instance
     * @param Schema    $schema
     * @param Structure $structure
     * @param array{}   $data
     */
    public function __construct(Schema $schema, Structure $structure, array $data) {
        $this->schema    = $schema;

        $this->name      = $data["name"];
        $this->idKey     = !empty($data["idKey"])   ? $data["idKey"]   : $structure->idKey;
        $this->idName    = !empty($data["idName"])  ? $data["idName"]  : $structure->idName;

        $this->hasWhere  = !empty($data["where"]);
        $this->where     = !empty($data["where"])   ? $data["where"]   : [];

        $this->hasOrder  = !empty($data["orderBy"]);
        $this->orderBy   = !empty($data["orderBy"]) ? $data["orderBy"] : "";
        $this->isAsc     = !empty($data["isAsc"])   ? $data["isAsc"]   : false;

        $this->field     = !empty($data["field"])   ? $data["field"]   : "";
        $this->value     = !empty($data["value"])   ? $data["value"]   : null;
    }



    /**
     * Does the Request with a Sub Request
     * @param mixed[] $result
     * @return array{}[]
     */
    public function request(array $result): array {
        $ids   = Arrays::createArray($result, $this->idName);
        $query = Query::create($this->idKey, "IN", $ids);

        if ($this->hasWhere) {
            $query->add($this->where[0], $this->where[1], $this->where[2]);
        }
        if ($this->hasOrder) {
            $query->orderBy($this->orderBy, $this->isAsc);
        }
        $request   = !empty($ids) ? $this->schema->getAll($query) : [];
        $subResult = [];

        foreach ($request as $row) {
            $name = $row[$this->idName];
            if (empty($subResult[$name])) {
                $subResult[$name] = [];
            }
            if (!empty($this->field)) {
                $subResult[$name][$row[$this->field]] = $this->getValues($row);
            } else {
                $subResult[$name][] = $this->getValues($row);
            }
        }

        foreach ($result as $index => $row) {
            $result[$index][$this->name] = [];
            foreach ($subResult as $key => $subRow) {
                if ($row[$this->idName] == $key) {
                    $result[$index][$this->name] = $subRow;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Values depending on the Data
     * @param array{} $row
     * @return mixed
     */
    private function getValues(array $row): mixed {
        if (empty($this->value)) {
            return $row;
        }
        if (is_array($this->value)) {
            $result = [];
            foreach ($this->value as $value) {
                $result[$value] = $row[$value];
            }
            return $result;
        }
        return $row[$this->value];
    }
}
