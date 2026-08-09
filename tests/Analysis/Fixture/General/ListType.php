<?php
namespace Tests\Analysis\Fixture\General;

class ListType {

    /** @var string[] */
    private array $reportedProperty = [];

    /**
     * A sequential array written with the bracket suffix
     * @param string[] $names
     * @return int[]
     */
    public function reported(array $names): array {
        return array_map("strlen", $names);
    }

    /**
     * The same thing said as a list
     * @param list<string> $names
     * @return list<int>
     */
    public function allowed(array $names): array {
        return array_map("strlen", $names);
    }

    /**
     * A keyed array, which is not a list at all
     * @param array<string,string> $values
     * @return array<string,string>
     */
    public function allowedKeyed(array $values): array {
        return $values;
    }
}
