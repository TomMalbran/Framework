<?php
namespace Framework\Database\Query;

use Framework\Database\Query;
use Framework\Database\Query\BaseQuery;
use Framework\System\Status;

/**
 * The Status Query
 */
class StatusQuery extends BaseQuery {

    /**
     * Generates an Equal Query
     * @param Status       $status
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equal(Status $status, ?bool $condition = null): Query {
        if ($status === Status::None) {
            return $this->query;
        }
        return $this->query->addIf($this->column, "=", $status->name, condition: $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param Status       $status
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqual(Status $status, ?bool $condition = null): Query {
        if ($status === Status::None) {
            return $this->query;
        }
        return $this->query->addIf($this->column, "<>", $status->name, condition: $condition);
    }



    /**
     * Generates an In Query
     * @param Status[]     $statuses
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function in(array $statuses, ?bool $condition = null): Query {
        if (empty($statuses)) {
            return $this->query;
        }
        $values = $this->getValues($statuses);
        return $this->query->add($this->column, "IN", $values, condition: $condition);
    }

    /**
     * Generates a Not In Query
     * @param Status[]     $statuses
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notIn(array $statuses, ?bool $condition = null): Query {
        if (empty($statuses)) {
            return $this->query;
        }
        $values = $this->getValues($statuses);
        return $this->query->add($this->column, "NOT IN", $values, condition: $condition);
    }

    /**
     * Returns the Values of the given Statuses
     * @param Status[] $statuses
     * @return string[]
     */
    private function getValues(array $statuses): array {
        $values = [];
        foreach ($statuses as $status) {
            $values[] = $status->name;
        }
        return $values;
    }
}
