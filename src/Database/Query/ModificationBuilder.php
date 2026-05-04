<?php
namespace Framework\Database\Query;

use Framework\IO\Request;
use Framework\Database\SchemaModel;
use Framework\Database\Query\QueryBuilder;
use Framework\Date\Date;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;

/**
 * The Modification Builder
 * @phpstan-import-type QueryValue from QueryBuilder
 */
class ModificationBuilder {

    private Query $builder;
    private SchemaModel $schemaModel;


    /**
     * Creates a new Modification instance
     * @param Query       $builder
     * @param SchemaModel $schemaModel
     */
    private function __construct(Query $builder, SchemaModel $schemaModel) {
        $this->builder     = $builder;
        $this->schemaModel = $schemaModel;
    }

    /**
     * Creates a new Insert Modification instance
     * @param SchemaModel $schemaModel
     * @return ModificationBuilder
     */
    public static function insert(SchemaModel $schemaModel): ModificationBuilder {
        $builder = Query::insert($schemaModel->tableName);
        return new ModificationBuilder($builder, $schemaModel);
    }

    /**
     * Creates a new Replace Modification instance
     * @param SchemaModel $schemaModel
     * @return ModificationBuilder
     */
    public static function replace(SchemaModel $schemaModel): ModificationBuilder {
        $builder = Query::replace($schemaModel->tableName);
        return new ModificationBuilder($builder, $schemaModel);
    }

    /**
     * Creates a new Update Modification instance
     * @param SchemaModel $schemaModel
     * @param Query       $query
     * @return ModificationBuilder
     */
    public static function update(SchemaModel $schemaModel, Query $query): ModificationBuilder {
        $builder = Query::update($query);
        return new ModificationBuilder($builder, $schemaModel);
    }


    /**
     * Returns the Fields
     * @return Dictionary
     */
    public function getFields(): Dictionary {
        return $this->builder->getFields();
    }

    /**
     * Sets a Field
     * @param string     $key
     * @param QueryValue $value
     * @return ModificationBuilder
     */
    public function setField(string $key, mixed $value): ModificationBuilder {
        $this->builder->set($key, $value);
        return $this;
    }

    /**
     * Adds all the Fields
     * @param Request|null             $request
     * @param array<string,QueryValue> $fields    Optional.
     * @param bool                     $skipEmpty Optional.
     * @param bool                     $skipUnset Optional.
     * @return ModificationBuilder
     */
    public function addFields(
        ?Request $request,
        array $fields = [],
        bool $skipEmpty = false,
        bool $skipUnset = false,
    ): ModificationBuilder {
        if ($request !== null) {
            $this->builder->fields($this->parseFields($request, $skipEmpty, $skipUnset));
        }
        if (count($fields) > 0) {
            $this->builder->fields($fields);
        }
        return $this;
    }

    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @param bool    $skipEmpty Optional.
     * @param bool    $skipUnset Optional.
     * @return array<string,QueryValue>
     */
    private function parseFields(Request $request, bool $skipEmpty = false, bool $skipUnset = false): array {
        $result = [];
        foreach ($this->schemaModel->fields as $field) {
            if ($skipUnset && !$request->exists($field->name)) {
                continue;
            }

            $value = $field->getValue($request);
            if ($value === null || ($skipEmpty && Arrays::isEmpty($value))) {
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
     * @param int $credentialID Optional.
     * @return ModificationBuilder
     */
    public function addCreation(int $credentialID = 0): ModificationBuilder {
        if ($this->schemaModel->canDelete && !$this->builder->hasField("isDeleted")) {
            $this->builder->set("isDeleted", 0);
        }

        if ($this->schemaModel->canCreate) {
            if ($this->schemaModel->hasTimestamps && !$this->builder->hasField("createdTime")) {
                $this->builder->set("createdTime", Date::now());
            }
            if ($this->schemaModel->hasUsers && $credentialID !== 0) {
                $this->builder->set("createdUser", $credentialID);
            }
        }
        return $this;
    }

    /**
     * Adds the Modification Fields
     * @param int  $credentialID   Optional.
     * @param bool $skipTimestamps Optional.
     * @return ModificationBuilder
     */
    public function addModification(int $credentialID = 0, bool $skipTimestamps = false): ModificationBuilder {
        if (!$this->schemaModel->canEdit || $skipTimestamps) {
            return $this;
        }

        if ($this->schemaModel->hasTimestamps && !$this->builder->hasField("modifiedTime")) {
            $this->builder->set("modifiedTime", Date::now());
        }
        if ($this->schemaModel->hasUsers && $credentialID !== 0) {
            $this->builder->set("modifiedUser", $credentialID);
        }
        return $this;
    }



    /**
     * Executes the Modification
     * @return int
     */
    public function execute(): int {
        return $this->builder->execute();
    }
}
