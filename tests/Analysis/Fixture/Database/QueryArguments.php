<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Auth\Schema\CredentialColumn;
use Framework\Auth\Schema\CredentialQuery;
use Framework\Database\Query\Exp;
use Framework\Database\Query\Op;
use Framework\Database\Query\Query;

class QueryArguments {

    public function reported(): string {
        $query = Query::select("crates", "c");
        $query->join("boxes", "b", "b.crateID = c.crateID");
        $query->where("c.status", "IS SOMETHING", 1);

        return $query->toSQL();
    }

    public function withoutAnOperator(): string {
        $query = Query::select("crates");
        $query->where("c.status");

        return $query->toSQL();
    }

    public function onAGeneratedQuery(): string {
        // The typed layer takes the same arguments, and drops the condition just
        // as quietly, so it is asked for the same things
        $query = new CredentialQuery();
        $query->where(CredentialColumn::Email);

        return $query->getQuery()->toSQL();
    }

    public function allowed(): string {
        $query = Query::select("crates", as: "c");
        $query->join("boxes", as: "b", on: "b.crateID = c.crateID");
        $query->where("c.status", "=", 1);

        // An Expression carries its own operator, so it needs none beside it
        $query->where(Exp::column("c.deletedTime")->isNotNull());

        // An Expression sits on either side, whatever the operator. The text ones
        // put their wildcards around its SQL instead of around a bound value
        $query->where("c.name", Op::Equal, Exp::column("c.other"));
        $query->where("c.count", ">", Exp::column("c.total"));
        $query->where("c.path", Op::StartsWith, Exp::column("c.parent"));
        $query->where("c.name", "ENDS", Exp::column("c.suffix"));

        return $query->toSQL();
    }
}
