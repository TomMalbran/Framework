<?php
namespace Framework\Schema;

use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\Query;
use Framework\Utils\Arrays;

/**
 * The Schema SubRequests
 */
class SubRequest {

    private Schema $schema;

    private string $name     = "";
    private string $idKey    = "";
    private string $idName   = "";

    /** @var mixed[] */
    private array  $where    = [];

    private bool   $hasOrder = false;
    private string $orderBy  = "";
    private bool   $isAsc    = false;

    private bool   $asArray  = false;
    private string $field    = "";
    private mixed  $value    = null;


    /**
     * Creates a new SubRequest instance
     * @param Schema    $schema
     * @param Structure $structure
     * @param array{}   $data
     */
    public function __construct(Schema $schema, Structure $structure, array $data) {
        $this->schema   = $schema;

        $this->name     = $data["name"];
        $this->idKey    = !empty($data["idKey"])   ? $data["idKey"]   : $structure->idKey;
        $this->idName   = !empty($data["idName"])  ? $data["idName"]  : $structure->idName;
        $this->where    = !empty($data["where"])   ? $data["where"]   : [];

        $this->hasOrder = !empty($data["orderBy"]);
        $this->orderBy  = !empty($data["orderBy"]) ? $data["orderBy"] : "";
        $this->isAsc    = !empty($data["isAsc"])   ? $data["isAsc"]   : false;

        $this->asArray  = !empty($data["asArray"]);
        $this->field    = !empty($data["field"])   ? $data["field"]   : "";
        $this->value    = !empty($data["value"])   ? $data["value"]   : null;
    }



    /**
     * Does the Request with a Sub Request
     * @param mixed[] $result
     * @return array{}[]
     */
    public function request(array $result): array {
        $query     = self::createQuery($result);
        $request   = !empty($query) ? $this->schema->getAll($query) : [];
        $subResult = [];

        foreach ($request as $row) {
            $name = $row[$this->idName];
            if (empty($subResult[$name])) {
                $subResult[$name] = [];
            }

            if (empty($this->field)) {
                $subResult[$name][] = $this->getValues($row);
                continue;
            }

            $field = $row[$this->field];
            if (!$this->asArray) {
                $subResult[$name][$field] = $this->getValues($row);
                continue;
            }

            if (empty($subResult[$name][$field])) {
                $subResult[$name][$field] = [];
            }
            $subResult[$name][$field][] = $this->getValues($row);
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
     * Does the Request with a Sub Request
     * @param mixed[] $result
     * @return Query|null
     */
    public function createQuery(array $result): ?Query {
        $ids = Arrays::createArray($result, $this->idName);
        if (empty($ids)) {
            return null;
        }
        $query = Query::create($this->idKey, "IN", $ids);

        $total = count($this->where);
        if ($total % 3 == 0) {
            for ($i = 0; $i < $total; $i += 3) {
                $query->add($this->where[$i], $this->where[$i + 1], $this->where[$i + 2]);
            }
        }

        if ($this->hasOrder) {
            $query->orderBy($this->orderBy, $this->isAsc);
        }
        return $query;
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
        if (Arrays::isArray($this->value)) {
            $result = [];
            foreach ($this->value as $value) {
                $result[$value] = $row[$value];
            }
            return $result;
        }
        return $row[$this->value];
    }
}
