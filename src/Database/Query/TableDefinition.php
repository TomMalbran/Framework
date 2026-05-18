<?php
namespace Framework\Database\Query;

/**
 * The Query Table Definition
 */
class TableDefinition {

    private ?Query $query = null;
    private string $name = "";
    private string $as = "";


    /**
     * Creates a new Table Definition
     * @param Query|string $queryOrTable
     * @param string       $as           Optional.
     */
    public function __construct(Query|string $queryOrTable, string $as = "") {
        if ($queryOrTable instanceof Query) {
            $this->query = $queryOrTable;
        } else {
            $this->name = $queryOrTable;
        }
        $this->as = $as;
    }

    /**
     * Returns the Name of the Table
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Converts the Table Definition to an SQL expression
     * @return string
     */
    public function toSQL(): string {
        $result = "";
        if ($this->query !== null) {
            $result = "({$this->query->toSQL()})";
        } else {
            $result = "`{$this->name}`";
        }
        if ($this->as !== "") {
            $result .= " AS `{$this->as}`";
        }
        return $result;
    }

    /**
     * Returns the bindings for the Query Builder
     * @return list<float|int|string>
     */
    public function getBindings(): array {
        if ($this->query !== null) {
            return $this->query->getBindings();
        }
        return [];
    }
}
