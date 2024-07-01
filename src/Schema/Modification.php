<?php
namespace Framework\Schema;

use Framework\Request;
use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\Schema\Query;
use Framework\Utils\Arrays;

/**
 * The Schema Modification
 */
class Modification {

    private Database  $db;
    private Structure $structure;

    /** @var array{} */
    private array $fields;
    private int   $credentialID;


    /**
     * Creates a new Modification instance
     * @param Database  $db
     * @param Structure $structure
     */
    public function __construct(Database $db, Structure $structure) {
        $this->db        = $db;
        $this->structure = $structure;
    }



    /**
     * Adds all the Fields
     * @param Request|array{}      $fields
     * @param array{}|integer|null $extra        Optional.
     * @param integer              $credentialID Optional.
     * @param boolean              $skipEmpty    Optional.
     * @return Modification
     */
    public function addFields(Request|array $fields, array|int $extra = null, int $credentialID = 0, bool $skipEmpty = false): Modification {
        if ($fields instanceof Request) {
            $this->fields = $this->parseFields($fields, $skipEmpty);
        } else {
            $this->fields = $fields;
        }
        if (!empty($extra)) {
            if (Arrays::isArray($extra)) {
                $this->fields = array_merge($this->fields, $extra);
            } else {
                $this->credentialID = $extra;
            }
        }
        if (!empty($credentialID)) {
            $this->credentialID = $credentialID;
        }
        return $this;
    }

    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @param boolean $skipEmpty Optional.
     * @return array{}
     */
    private function parseFields(Request $request, bool $skipEmpty = false): array {
        $result = [];
        foreach ($this->structure->fields as $field) {
            if (!$field->canEdit) {
                continue;
            }
            $value = $field->fromRequest($request, $this->structure->masterKey);
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
     * @return Modification
     */
    public function addCreation(): Modification {
        if ($this->structure->canDelete && empty($this->fields["isDeleted"])) {
            $this->fields["isDeleted"] = 0;
        }

        if ($this->structure->canCreate) {
            if ($this->structure->hasTimestamps && empty($this->fields["createdTime"])) {
                $this->fields["createdTime"] = time();
            }
            if ($this->structure->hasUsers && !empty($this->credentialID)) {
                $this->fields["createdUser"] = $this->credentialID;
            }
        }
        return $this;
    }

    /**
     * Adds the Modification Fields
     * @return Modification
     */
    public function addModification(): Modification {
        if (!$this->structure->canEdit) {
            return $this;
        }

        if ($this->structure->hasTimestamps && empty($this->fields["modifiedTime"])) {
            $this->fields["modifiedTime"] = time();
        }
        if ($this->structure->hasUsers && !empty($this->credentialID)) {
            $this->fields["modifiedUser"] = $this->credentialID;
        }
        return $this;
    }



    /**
     * Inserts the Fields into the Database
     * @return integer
     */
    public function insert(): int {
        return $this->db->insert($this->structure->table, $this->fields);
    }

    /**
     * Replaces the Fields into the Database
     * @return integer
     */
    public function replace(): int {
        return $this->db->insert($this->structure->table, $this->fields, "REPLACE");
    }

    /**
     * Updates the Fields in the Database
     * @param Query $query
     * @return boolean
     */
    public function update(Query $query): bool {
        return $this->db->update($this->structure->table, $this->fields, $query);
    }
}
