<?php
namespace Framework\Schema;

use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\Schema\Query;
use Framework\Request;

/**
 * The Modification Wrapper
 */
class Modification {
    
    private $db;
    private $structure;

    private $fields;
    private $credentialID;


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
     * @param Request|array $fields
     * @param array|integer $extra        Optional.
     * @param integer       $credentialID Optional.
     * @return void
     */
    public function addFields($fields, $extra = null, $credentialID = 0) {
        if ($fields instanceof Request) {
            $this->fields = $this->parseFields($fields);
        } else {
            $this->fields = $fields;
        }
        if (!empty($extra)) {
            if (is_array($extra)) {
                $this->fields = array_merge($this->fields, $extra);
            } else {
                $this->credentialID = $extra;
            }
        }
        if (!empty($credentialID)) {
            $this->credentialID = $credentialID;
        }
    }

    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @return array
     */
    private function parseFields(Request $request) {
        $result = [];
        foreach ($this->structure->fields as $field) {
            if ($field->canEdit) {
                $value = $field->fromRequest($request, $this->structure->masterKey);

                if (!$field->noEmpty && !empty($value)) {
                    $result[$field->key] = $value;
                } elseif ($field->noEmpty && $value !== null) {
                    $result[$field->key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Adds the Creation Fields
     * @return void
     */
    public function addCreation() {
        if ($this->structure->canDelete && empty($this->fields["isDeleted"])) {
            $this->fields["isDeleted"] = 0;
        }
        if ($this->structure->hasTimestamps && empty($this->fields["createdTime"])) {
            $this->fields["createdTime"] = time();
        }
        if ($this->structure->hasUsers && !empty($this->credentialID)) {
            $this->fields["createdUser"] = $this->credentialID;
        }
    }

    /**
     * Adds the Modification Fields
     * @return void
     */
    public function addModification() {
        if ($this->structure->canEdit && $this->structure->hasTimestamps) {
            $this->fields["modifiedTime"] = time();
        }
        if ($this->structure->canEdit && $this->structure->hasUsers && !empty($this->credentialID)) {
            $this->fields["modifiedUser"] = $this->credentialID;
        }
    }



    /**
     * Inserts the Fields into the Database
     * @return integer
     */
    public function insert() {
        return $this->db->insert($this->structure->table, $this->fields);
    }

    /**
     * Replaces the Fields into the Database
     * @return integer
     */
    public function replace() {
        return $this->db->insert($this->structure->table, $this->fields, "REPLACE");
    }

    /**
     * Updates the Fields in the Database
     * @param Query $query
     * @return boolean
     */
    public function update(Query $query) {
        return $this->db->update($this->structure->table, $this->fields, $query);
    }
}
