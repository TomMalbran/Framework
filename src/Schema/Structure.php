<?php
namespace Framework\Schema;

use Framework\Schema\KeyChain;
use Framework\Schema\Field;
use Framework\Schema\Join;
use Framework\Schema\Count;
use Framework\Utils\Strings;

/**
 * The Database Structure
 */
class Structure {

    public $masterKey     = "";

    public $table         = "";
    public $idKey         = "";
    public $idName        = "";
    public $name          = "";
    
    public $fields        = [];
    public $joins         = [];
    public $counts        = [];

    public $hasPositions  = false;
    public $hasTimestamps = false;
    public $hasUsers      = false;
    public $canCreate     = false;
    public $canDelete     = false;


    /**
     * Creates a new Structure instance
     * @param string $schemaKey
     * @param array  $data
     */
    public function __construct($schemaKey, array $data) {
        $this->table         = $data["table"];
        $this->hasPositions  = !empty($data["hasPositions"])  && $data["hasPositions"];
        $this->hasTimestamps = !empty($data["hasTimestamps"]) && $data["hasTimestamps"];
        $this->hasUsers      = !empty($data["hasUsers"])      && $data["hasUsers"];
        $this->canCreate     = $data["canCreate"];
        $this->canEdit       = $data["canEdit"];
        $this->canDelete     = $data["canDelete"];

        // Add additional Fields
        if ($this->hasPositions) {
            $data["fields"]["position"] = [
                "type" => Field::Number,
            ];
        }
        if ($this->canCreate && $this->hasTimestamps) {
            $data["fields"]["createdTime"] = [
                "type"     => Field::Date,
                "cantEdit" => true,
            ];
        }
        if ($this->canCreate && $this->hasUsers) {
            $data["fields"]["createdUser"] = [
                "type"     => Field::Number,
                "cantEdit" => true,
            ];
        }
        if ($this->canEdit && $this->hasTimestamps) {
            $data["fields"]["modifiedTime"] = [
                "type"     => Field::Date,
                "cantEdit" => true,
            ];
        }
        if ($this->canEdit && $this->hasUsers) {
            $data["fields"]["modifiedUser"] = [
                "type"     => Field::Number,
                "cantEdit" => true,
            ];
        }
        if ($this->canDelete) {
            $data["fields"]["isDeleted"] = [
                "type"     => Field::Boolean,
                "cantEdit" => true,
            ];
        }

        // Parse all the Fields
        $reqMasterkey = false;
        foreach ($data["fields"] as $key => $value) {
            $field = new Field($key, $value);
            if ($field->isID) {
                $this->idKey  = $field->key;
                $this->idName = $field->name;
            }
            if ($field->isName) {
                $this->name = $field->type == Field::Text ? "{$field->key}Short" : $field->key;
            }
            if ($field->type == Field::Encrypt) {
                $reqMasterkey = true;
            }
            $this->fields[] = $field;
        }

        // Parse all the Joins
        if (!empty($data["joins"])) {
            foreach ($data["joins"] as $key => $value) {
                $this->joins[] = new Join($key, $value);
            }
        }

        // Parse all the Counts
        if (!empty($data["counts"])) {
            foreach ($data["counts"] as $key => $value) {
                $this->counts[] = new Count($key, $value);
            }
        }

        // Set the Masterkey
        if ($reqMasterkey) {
            $this->masterKey = KeyChain::get($schemaKey);
        }
    }



    /**
     * Returns the Key adding the table as the prefix
     * @param string $key
     * @return string
     */
    public function getKey($key) {
        if (!Strings::contains($key, ".")) {
            $mainKey = $this->table;
            return "{$mainKey}.{$key}";
        }
        return $key;
    }
}
