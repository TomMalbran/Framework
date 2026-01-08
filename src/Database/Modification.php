<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Request;
use Framework\Database\SchemaModel;
use Framework\Database\Query\Query;
use Framework\Utils\Arrays;

/**
 * The Schema Modification
 */
class Modification {

    private SchemaModel $schemaModel;

    /** @var array<string,mixed> */
    private array $fields;


    /**
     * Creates a new Modification instance
     * @param SchemaModel $schemaModel
     */
    public function __construct(SchemaModel $schemaModel) {
        $this->schemaModel = $schemaModel;
        $this->fields      = [];
    }


    /**
     * Returns the Fields
     * @return array<string,mixed>
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * Sets a Field
     * @param string $key
     * @param mixed  $value
     * @return Modification
     */
    public function setField(string $key, mixed $value): Modification {
        $this->fields[$key] = $value;
        return $this;
    }

    /**
     * Adds all the Fields
     * @param Request|null        $request
     * @param array<string,mixed> $fields    Optional.
     * @param boolean             $skipEmpty Optional.
     * @param boolean             $skipUnset Optional.
     * @return Modification
     */
    public function addFields(
        ?Request $request,
        array $fields = [],
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): Modification {
        if ($request !== null) {
            $this->fields = $this->parseFields($request, $skipEmpty, $skipUnset);
        }
        if (count($fields) > 0) {
            $this->fields = array_merge($this->fields, $fields);
        }
        return $this;
    }

    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @param boolean $skipEmpty Optional.
     * @param boolean $skipUnset Optional.
     * @return array<string,mixed>
     */
    private function parseFields(Request $request, bool $skipEmpty = false, bool $skipUnset = false): array {
        $result = [];
        foreach ($this->schemaModel->fields as $field) {
            if (!$field->fromRequest) {
                continue;
            }
            if ($skipUnset && !$request->exists($field->name)) {
                continue;
            }

            $value = $field->getValue($request);
            if ($skipEmpty && Arrays::isEmpty($value)) {
                continue;
            }

            if ($field->noExists) {
                if ($request->exists($field->name)) {
                    $result[$field->dbName] = $value;
                }
            } elseif ($field->noEmpty) {
                if (!Arrays::isEmpty($value)) {
                    $result[$field->dbName] = $value;
                }
            } elseif ($value !== null) {
                $result[$field->dbName] = $value;
            }
        }
        return $result;
    }

    /**
     * Adds the Creation Fields
     * @param integer $credentialID Optional.
     * @return Modification
     */
    public function addCreation(int $credentialID = 0): Modification {
        if ($this->schemaModel->canDelete && Arrays::isEmpty($this->fields, "isDeleted")) {
            $this->fields["isDeleted"] = 0;
        }

        if ($this->schemaModel->canCreate) {
            if ($this->schemaModel->hasTimestamps && Arrays::isEmpty($this->fields, "createdTime")) {
                $this->fields["createdTime"] = time();
            }
            if ($this->schemaModel->hasUsers && $credentialID !== 0) {
                $this->fields["createdUser"] = $credentialID;
            }
        }
        return $this;
    }

    /**
     * Adds the Modification Fields
     * @param integer $credentialID Optional.
     * @return Modification
     */
    public function addModification(int $credentialID = 0): Modification {
        if (!$this->schemaModel->canEdit) {
            return $this;
        }

        if ($this->schemaModel->hasTimestamps && Arrays::isEmpty($this->fields, "modifiedTime")) {
            $this->fields["modifiedTime"] = time();
        }
        if ($this->schemaModel->hasUsers && $credentialID !== 0) {
            $this->fields["modifiedUser"] = $credentialID;
        }
        return $this;
    }



    /**
     * Inserts the Fields into the Database
     * @return integer
     */
    public function insert(): int {
        return Framework::getDatabase()->insert(
            $this->schemaModel->tableName,
            $this->fields,
        );
    }

    /**
     * Replaces the Fields into the Database
     * @return integer
     */
    public function replace(): int {
        return Framework::getDatabase()->replace(
            $this->schemaModel->tableName,
            $this->fields,
        );
    }

    /**
     * Updates the Fields in the Database
     * @param Query $query
     * @return boolean
     */
    public function update(Query $query): bool {
        return Framework::getDatabase()->update(
            $this->schemaModel->tableName,
            $this->fields,
            $query,
        );
    }
}
