<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Database\Selection;
use Framework\Database\Query;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use Attribute;

/**
 * The SubRequest Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SubRequest {

    public string $modelName = "";
    public string $idName    = "";
    public string $fieldName = "";
    public string $valueName = "";
    public string $query     = "";


    /**
     * The SubRequest Attribute
     * @param string $modelName Optional.
     * @param string $idName    Optional.
     * @param string $fieldName Optional.
     * @param string $valueName Optional.
     * @param string $query     Optional.
     */
    public function __construct(
        string $modelName = "",
        string $idName    = "",
        string $fieldName = "",
        string $valueName = "",
        string $query     = "",
    ) {
        $this->modelName = SchemaModel::getBaseModelName($modelName);
        $this->idName    = $idName;
        $this->fieldName = $fieldName;
        $this->valueName = $valueName;
        $this->query     = $query;
    }



    // Used internally when parsing the Model
    public ?SchemaModel $schemaModel = null;
    public ?SchemaModel $parentModel = null;
    public string $name      = "";
    public string $idDbName  = "";
    public string $type      = "";
    public string $namespace = "";
    public string $className = "";


    /**
     * Creates a SubRequest
     * @param SchemaModel $schemaModel
     * @param string      $name
     * @param string      $idName
     * @param string      $idDbName
     * @param string      $fieldName
     * @param string      $valueName
     * @param string      $query
     * @return SubRequest
     */
    public static function create(
        SchemaModel $schemaModel,
        string      $name,
        string      $idName,
        string      $idDbName,
        string      $fieldName,
        string      $valueName,
        string      $query,
    ): SubRequest {
        $result = new self("", $idName, $fieldName, $valueName, $query);
        $result->name        = $name;
        $result->idDbName    = $idDbName;
        $result->schemaModel = $schemaModel;
        return $result;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $modelName
     * @param string $type
     * @return SubRequest
     */
    public function setData(string $name, string $type, string $modelName): SubRequest {
        $this->name = $name;

        if ($type !== "") {
            $this->type = $type;
        }
        if ($modelName !== "") {
            $this->modelName = $modelName;
        }
        return $this;
    }

    /**
     * Sets the Model for the SubRequest
     * @param SchemaModel $schemaModel
     * @param SchemaModel $parentModel
     * @return SubRequest
     */
    public function setModel(SchemaModel $schemaModel, SchemaModel $parentModel): SubRequest {
        $this->schemaModel = $schemaModel;
        $this->parentModel = $parentModel;
        $this->namespace   = $schemaModel->namespace;
        $this->className   = "{$schemaModel->name}Schema";
        return $this;
    }



    /**
     * Does the Request with a Sub Request
     * @param array<string,mixed>[] $result
     * @return array<string,mixed>[]
     */
    public function request(array $result): array {
        $query = $this->createQuery($result);
        if ($query === null || $this->schemaModel === null) {
            return [];
        }

        $selection = new Selection($this->schemaModel);
        $selection->addFields();
        $selection->addExpressions();
        $selection->addJoins();
        $selection->addCounts();
        $selection->request($query);
        $request   = $selection->resolve();
        $subResult = [];

        foreach ($request as $row) {
            if (!isset($row[$this->idName])) {
                continue;
            }

            $name = $row[$this->idName];
            if (!isset($subResult[$name])) {
                $subResult[$name] = [];
            }

            if ($this->fieldName === "") {
                $subResult[$name][] = $this->getValues($row);
                continue;
            }

            $field = $row[$this->fieldName];
            $subResult[$name][$field] = $this->getValues($row);
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
     * Does the Request with a Sub Request
     * @param mixed[] $result
     * @return Query|null
     */
    private function createQuery(array $result): ?Query {
        $ids = Arrays::createArray($result, $this->idName);
        if (count($ids) === 0) {
            return null;
        }

        $query = Query::create($this->idDbName, "IN", $ids);

        if ($this->query !== "") {
            $queryParts = Strings::split($this->query, " ");
            $total      = count($queryParts);
            if ($total % 3 === 0) {
                for ($i = 0; $i < $total; $i += 3) {
                    $query->add($queryParts[$i], $queryParts[$i + 1], $queryParts[$i + 2]);
                }
            }
        }

        if ($this->schemaModel !== null) {
            if ($this->schemaModel->canDelete) {
                $isDeleted = $this->schemaModel->getKey("isDeleted");
                $query->add($isDeleted, "=", 0);
            }
            if ($this->schemaModel->hasPositions) {
                $query->orderBy($this->schemaModel->getKey("position"), true);
            }
        }
        return $query;
    }

    /**
     * Returns the Values depending on the Data
     * @param array<string,mixed> $row
     * @return mixed
     */
    private function getValues(array $row): mixed {
        if ($this->valueName !== "") {
            return $row[$this->valueName];
        }
        return $row;
    }



    /**
     * Returns the Data to build a SubRequest
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        $idName   = "";
        $idDbName = "";
        if ($this->idName !== "") {
            $idName   = $this->idName;
            $idDbName = SchemaModel::getDbFieldName($this->idName);
        } elseif ($this->parentModel !== null) {
            $idName   = $this->parentModel->idName;
            $idDbName = $this->parentModel->idDbName;
        }

        return [
            "schemaModel" => $this->className,
            "name"        => $this->name,
            "idName"      => $idName,
            "idDbName"    => $idDbName,
            "fieldName"   => $this->fieldName,
            "valueName"   => $this->valueName,
            "query"       => $this->query,
        ];
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $result = [
            "name" => $this->name,
        ];
        if ($this->type !== "") {
            $result["type"] = $this->type;
        }
        if ($this->idName !== "") {
            $result["idKey"]  = SchemaModel::getDbFieldName($this->idName);
            $result["idName"] = $this->idName;
        }
        if ($this->fieldName !== "") {
            $result["field"] = $this->fieldName;
        }
        if ($this->valueName !== "") {
            $result["value"] = $this->valueName;
        }
        if ($this->query !== "") {
            $result["where"] = Strings::split($this->query, " ");
        }
        if ($this->schemaModel !== null && $this->schemaModel->hasPositions) {
            $result["orderBy"] = "position";
            $result["isAsc"]   = true;
        }
        return $result;
    }
}
