<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Request;
use Framework\Database\Structure;
use Framework\Database\Query;

/**
 * The Schema Modification
 */
class Modification {

    private Structure $structure;

    /** @var array<string,mixed> */
    private array $fields;


    /**
     * Creates a new Modification instance
     * @param Structure $structure
     */
    public function __construct(Structure $structure) {
        $this->structure = $structure;
        $this->fields    = [];
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
        if (!empty($fields)) {
            $this->fields = array_merge($this->fields, $fields);
        }
        return $this;
    }

    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @param boolean $skipEmpty Optional.
     * @param boolean $skipUnset Optional.
     * @return array{}
     */
    private function parseFields(Request $request, bool $skipEmpty = false, bool $skipUnset = false): array {
        $result = [];
        foreach ($this->structure->fields as $field) {
            if (!$field->canEdit) {
                continue;
            }
            if ($skipUnset && !$request->exists($field->name)) {
                continue;
            }

            $value = $field->fromRequest($request);
            if ($skipEmpty && empty($value)) {
                continue;
            }

            if ($field->noExists) {
                if ($request->exists($field->name)) {
                    $result[$field->key] = $value;
                }
            } elseif ($field->noEmpty) {
                if (!empty($value)) {
                    $result[$field->key] = $value;
                }
            } elseif ($value !== null) {
                $result[$field->key] = $value;
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
        if ($this->structure->canDelete && empty($this->fields["isDeleted"])) {
            $this->fields["isDeleted"] = 0;
        }

        if ($this->structure->canCreate) {
            if ($this->structure->hasTimestamps && empty($this->fields["createdTime"])) {
                $this->fields["createdTime"] = time();
            }
            if ($this->structure->hasUsers && !empty($credentialID)) {
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
        if (!$this->structure->canEdit) {
            return $this;
        }

        if ($this->structure->hasTimestamps && empty($this->fields["modifiedTime"])) {
            $this->fields["modifiedTime"] = time();
        }
        if ($this->structure->hasUsers && !empty($credentialID)) {
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
            $this->structure->table,
            $this->fields,
        );
    }

    /**
     * Replaces the Fields into the Database
     * @return integer
     */
    public function replace(): int {
        return Framework::getDatabase()->insert(
            $this->structure->table,
            $this->fields,
            "REPLACE",
        );
    }

    /**
     * Updates the Fields in the Database
     * @param Query $query
     * @return boolean
     */
    public function update(Query $query): bool {
        return Framework::getDatabase()->update(
            $this->structure->table,
            $this->fields,
            $query,
        );
    }
}
