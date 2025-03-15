<?php
namespace Framework\Database;

use Framework\Database\Factory;
use Framework\Database\Field;
use Framework\Database\Join;
use Framework\Database\Count;
use Framework\Utils\Strings;

/**
 * The Schema Structure
 */
class Structure {

    public string $schema      = "";

    public string $table       = "";
    public bool   $hasID       = false;
    public bool   $hasAutoInc  = false;
    public string $idKey       = "";
    public string $idName      = "";
    public string $idType      = "";
    public string $nameKey     = "";

    /** @var Field[] */
    public array $fields       = [];

    /** @var Field[] */
    public array $expressions  = [];

    /** @var Field[] */
    public array $processed    = [];

    /** @var Join[] */
    public array $joins        = [];

    /** @var Count[] */
    public array $counts       = [];

    /** @var SubRequest[] */
    public array $subRequests  = [];

    public bool $hasStatus     = false;
    public bool $hasPositions  = false;
    public bool $hasTimestamps = false;
    public bool $hasUsers      = false;
    public bool $hasFilters    = false;
    public bool $hasEncrypt    = false;
    public bool $canCreate     = false;
    public bool $canEdit       = false;
    public bool $canDelete     = false;
    public bool $canRemove     = false;


    /**
     * Creates a new Structure instance
     * @param string              $schema
     * @param array<string,mixed> $data
     */
    public function __construct(string $schema, array $data) {
        $this->schema        = $schema;
        $this->table         = Factory::getTableName($schema);
        $this->hasStatus     = $data["hasStatus"]     ?? false;
        $this->hasPositions  = $data["hasPositions"]  ?? false;
        $this->hasTimestamps = $data["hasTimestamps"] ?? false;
        $this->hasUsers      = $data["hasUsers"]      ?? false;
        $this->hasFilters    = $data["hasFilters"]    ?? false;
        $this->canCreate     = $data["canCreate"]     ?? false;
        $this->canEdit       = $data["canEdit"]       ?? false;
        $this->canDelete     = $data["canDelete"]     ?? false;
        $this->canRemove     = $data["canRemove"]     ?? false;

        // Add additional Fields
        if ($this->hasStatus) {
            $data["fields"]["status"] = [
                "type"    => Field::String,
                "noEmpty" => true,
                "default" => "",
            ];
        }
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
        $reqMasterKey = false;
        foreach ($data["fields"] as $key => $value) {
            if ($value["type"] == Field::ID) {
                $data["fields"][$key]["isPrimary"] = true;
                $idKey = $key;
            }
            if (!empty($value["isPrimary"])) {
                $primaryCount += 1;
            }
            if ($idKey === "" && !empty($value["isPrimary"])) {
                $idKey = $key;
            }
            if ($value["type"] == Field::Encrypt) {
                $reqMasterKey = true;
            }
        }
        if ($primaryCount > 1) {
            $idKey = "";
        }

        // Create the Fields
        foreach ($data["fields"] as $key => $value) {
            $field = new Field($key, $value);
            if ($field->type == Field::ID) {
                $this->hasAutoInc = true;
            }
            if ($key == $idKey) {
                $this->hasID  = true;
                $this->idKey  = $field->key;
                $this->idName = $field->name;
                $this->idType = $field->type;
            }
            if ($field->isName) {
                $this->nameKey = $field->type == Field::Text ? "{$field->key}Short" : $field->key;
            }
            $this->fields[] = $field;
        }

        // Create the Expressions
        if (!empty($data["expressions"])) {
            foreach ($data["expressions"] as $key => $value) {
                $this->expressions[$value["expression"]] = new Field($key, $value);
            }
        }

        // Create the Processed
        if (!empty($data["processed"])) {
            foreach ($data["processed"] as $key => $value) {
                $this->processed[] = new Field($key, $value);
            }
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

        // Create the SubRequests
        if (!empty($data["subrequests"])) {
            foreach ($data["subrequests"] as $key => $value) {
                $subStructure = Factory::getStructure($key);
                $this->subRequests[] = new SubRequest($subStructure, $value, $this->idKey, $this->idName);
            }
        }

        // Set the Master Key
        if ($reqMasterKey) {
            $this->hasEncrypt = true;
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
        return $this->hasPositions ? "position" : $this->nameKey;
    }

    /**
     * Replaces the Table in the Expression
     * @param string $expression
     * @return string
     */
    public function replaceTable(string $expression): string {
        return Strings::replace($expression, "{table}", "`{$this->table}`");
    }
}
