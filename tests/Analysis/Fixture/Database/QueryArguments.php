<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Database\Query\Query;

class QueryArguments {

    public function reported(): string {
        $query = Query::select("crates", "c");
        $query->join("boxes", "b", "b.crateID = c.crateID");
        $query->where("c.status", "IS SOMETHING", 1);

        return $query->toSQL();
    }

    public function allowed(): string {
        $query = Query::select("crates", as: "c");
        $query->join("boxes", as: "b", on: "b.crateID = c.crateID");
        $query->where("c.status", "=", 1);

        return $query->toSQL();
    }
}
