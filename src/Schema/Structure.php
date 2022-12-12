<?php
namespace Framework\Schema;

use Framework\Schema\KeyChain;
use Framework\Schema\Field;
use Framework\Schema\Join;
use Framework\Schema\Count;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Structure
 */
class Structure {

    public string $masterKey    = "";

    public string $table        = "";
    public bool   $hasID        = false;
    public string $idKey        = "";
    public string $idName       = "";
    public string $name         = "";

    /** @var Field[] */
    public array $fields        = [];

    /** @var Field[] */
    public array $expressions   = [];

    /** @var Join[] */
    public array $joins         = [];

    /** @var Count[] */
    public array $counts        = [];

    public bool $hasPositions   = false;
    public bool $hasTimestamps  = false;
    public bool $hasUsers       = false;
    public bool $canCreate      = false;
    public bool $canDelete      = false;


    /**
     * Creates a new Structure instance
     * @param string  $schemaKey
     * @param array{} $data
     */
    public function __construct(string $schemaKey, array $data) {
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
                "type"    => Field::Number,
                "default" => 0,
            ];
        }
        if ($this->canCreate && $this->hasTimestamps) {
            $data["fields"]["createdTime"] = [
                "type"     => Field::Date,
                "cantEdit" => true,
                "default"  => 0,
            ];
        }
        if ($this->canCreate && $this->hasUsers) {
            $data["fields"]["createdUser"] = [
                "type"     => Field::Number,
                "cantEdit" => true,
                "default"  => 0,
            ];
        }
        if ($this->canEdit && $this->hasTimestamps) {
            $data["fields"]["modifiedTime"] = [
                "type"     => Field::Date,
                "cantEdit" => true,
                "default"  => 0,
            ];
        }
        if ($this->canEdit && $this->hasUsers) {
            $data["fields"]["modifiedUser"] = [
                "type"     => Field::Number,
                "cantEdit" => true,
                "default"  => 0,
            ];
        }
        if ($this->canDelete) {
            $data["fields"]["isDeleted"] = [
                "type"     => Field::Boolean,
                "cantEdit" => true,
                "default"  => 0,
            ];
        }

        // Parse the Fields
        $idKey        = "";
        $primaryCount = 0;
        $reqMasterkey = false;
        foreach ($data["fields"] as $key => $value) {
            if ($value["type"] == Field::ID) {
                $data["fields"][$key]["isPrimary"] = true;
                $idKey = $key;
            }
            if (!empty($value["isPrimary"])) {
                $primaryCount += 1;
            }
            if (empty($idKey) && !empty($value["isPrimary"])) {
                $idKey = $key;
            }
            if ($value["type"] == Field::Encrypt) {
                $reqMasterkey = true;
            }
        }
        if ($primaryCount > 1) {
            $idKey = "";
        }

        // Create the Fields
        foreach ($data["fields"] as $key => $value) {
            $field = new Field($key, $value);
            if ($key == $idKey) {
                $this->hasID  = true;
                $this->idKey  = $field->key;
                $this->idName = $field->name;
            }
            if ($field->isName) {
                $this->name = $field->type == Field::Text ? "{$field->key}Short" : $field->key;
            }
            $this->fields[] = $field;
        }

        // Create the Joins
        if (!empty($data["joins"])) {
            foreach ($data["joins"] as $key => $value) {
                $this->joins[] = new Join($key, $value);
            }
        }

        // Create the Counts
        if (!empty($data["counts"])) {
            foreach ($data["counts"] as $key => $value) {
                $this->counts[] = new Count($key, $value);
            }
        }

        // Parse the Expressions
        if (!empty($data["expressions"])) {
            foreach ($data["expressions"] as $key => $value) {
                if (Arrays::isArray($value)) {
                    $this->expressions[$value["expression"]] = new Field($key, $value);
                } else {
                    $this->expressions[$value] = new Field($key, []);
                }
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
    public function getKey(string $key): string {
        if (!Strings::contains($key, ".")) {
            $mainKey = $this->table;
            return "{$mainKey}.{$key}";
        }
        return $key;
    }

    /**
     * Returns the Order Field
     * @param string|null $field Optional.
     * @return string
     */
    public function getOrder(?string $field = null): string {
        if (!empty($field)) {
            return $field;
        }
        return $this->hasPositions ? "position" : $this->name;
    }
}
