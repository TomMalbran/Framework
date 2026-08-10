<?php
namespace Tests\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Relation;
use Framework\Database\SchemaModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Relation Attribute, which joins the table of another Model onto this one
 */
class RelationTest extends TestCase {

    /**
     * Returns a Model of the given name, holding the given fields
     * @param string     $name
     * @param list<Field> $fields Optional.
     * @return SchemaModel
     */
    private static function model(string $name, array $fields = []): SchemaModel {
        return new SchemaModel(name: $name, mainFields: $fields);
    }

    /**
     * Returns the User Model, which is what most of these relate to
     * @return SchemaModel
     */
    private static function userModel(): SchemaModel {
        return self::model("User", [
            Field::create(name: "userID", dbName: "USER_ID", type: FieldType::Number, isID: true),
            Field::create(name: "firstName", type: FieldType::String),
            Field::create(name: "lastName", type: FieldType::String),
        ]);
    }

    /**
     * Returns a Relation walked through the build steps it is given
     * @param Relation         $relation
     * @param SchemaModel|null $relationModel Optional.
     * @param SchemaModel|null $parentModel   Optional.
     * @return Relation
     */
    private static function build(
        Relation $relation,
        ?SchemaModel $relationModel = null,
        ?SchemaModel $parentModel = null,
    ): Relation {
        $relation->setDataFromAttribute("User", "user");
        $relation->parseRelationJoin();
        $relation->parseOwnerJoin();
        $relation->setModels($relationModel ?? self::userModel(), $parentModel ?? self::model("Credential"));
        return $relation;
    }



    public function testARelationIsCreatedWithEveryValue(): void {
        $fields   = [ Field::create(name: "firstName") ];
        $relation = Relation::create("User", "Owner", "USER_ID", "Credential", "CURRENT_USER", "a AND b", $fields);

        $this->assertSame("User", $relation->relationModelName);
        $this->assertSame("Owner", $relation->relationAliasName);
        $this->assertSame("USER_ID", $relation->relationFieldDbName);
        $this->assertSame("Credential", $relation->ownerModelName);
        $this->assertSame("CURRENT_USER", $relation->ownerFieldDbName);
        $this->assertSame("a AND b", $relation->ownerAndQuery);
        $this->assertSame($fields, $relation->fields);
    }

    /**
     * The prefix the fields of the relation are named with
     * @param string $given
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerPrefix")]
    public function testThePrefixFallsBackToTheNameOfTheProperty(
        string $given,
        string $expected,
    ): void {
        $relation = new Relation(prefix: $given, fieldNames: [ "firstName" ]);
        $relation->setDataFromAttribute("User", "owner");
        $relation->setModels(self::userModel(), self::model("Credential"));
        $relation->generateFields();

        $this->assertSame("User", $relation->relationModelName);
        $this->assertSame("owner", $relation->fieldName);
        $this->assertSame($expected, $relation->fields[0]->prefixName);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerPrefix(): array {
        return [
            "nothing given"  => [ "", "ownerFirstName" ],
            "one of its own" => [ "made", "madeFirstName" ],
        ];
    }



    /**
     * The relationJoin of the attribute, and what is read out of it
     * @param string $relationJoin
     * @param bool   $expected
     * @param string $aliasName
     * @param string $fieldName
     * @return void
     */
    #[DataProvider("providerRelationJoin")]
    public function testTheRelationJoinNamesTheFieldAndTheAlias(
        string $relationJoin,
        bool $expected,
        string $aliasName,
        string $fieldName,
    ): void {
        $relation = new Relation(relationJoin: $relationJoin);
        $relation->setDataFromAttribute("User", "user");

        $this->assertSame($expected, $relation->parseRelationJoin());
        $this->assertSame($aliasName, $relation->relationAliasName);
        $this->assertSame($fieldName, $relation->relationFieldName);
    }

    /**
     * A name before the dot that is not the Model of the property is an alias,
     * which is how one Model is joined twice
     * @return array<string,array{string,bool,string,string}>
     */
    public static function providerRelationJoin(): array {
        return [
            "nothing given"   => [ "", false, "", "" ],
            "a field alone"   => [ "userID", true, "", "userID" ],
            "the same model"  => [ "User.userID", true, "", "userID" ],
            "with the suffix" => [ "UserModel.userID", true, "", "userID" ],
            "another name"    => [ "Owner.userID", true, "Owner", "userID" ],
            "half of it"      => [ "Owner.", true, "Owner", "" ],
        ];
    }

    /**
     * The ownerJoin of the attribute, and what is read out of it
     * @param string $ownerJoin
     * @param bool   $expected
     * @param string $modelName
     * @param string $fieldName
     * @param string $andQuery
     * @return void
     */
    #[DataProvider("providerOwnerJoin")]
    public function testTheOwnerJoinNamesTheSideTheJoinIsMadeFrom(
        string $ownerJoin,
        bool $expected,
        string $modelName,
        string $fieldName,
        string $andQuery,
    ): void {
        $relation = new Relation(ownerJoin: $ownerJoin);
        $relation->setDataFromAttribute("User", "user");

        $this->assertSame($expected, $relation->parseOwnerJoin());
        $this->assertSame($modelName, $relation->ownerModelName);
        $this->assertSame($fieldName, $relation->ownerFieldName);
        $this->assertSame($andQuery, $relation->ownerAndQuery);
    }

    /**
     * Everything after the first AND is kept whole, to be read again when the
     * join is written
     * @return array<string,array{string,bool,string,string,string}>
     */
    public static function providerOwnerJoin(): array {
        return [
            "nothing given"   => [ "", false, "", "", "" ],
            "a field alone"   => [ "currentUser", true, "", "currentUser", "" ],
            "a model too"     => [ "Credential.currentUser", true, "Credential", "currentUser", "" ],
            "with the suffix" => [ "CredentialModel.currentUser", true, "Credential", "currentUser", "" ],
            "an and"          => [
                "Credential.currentUser AND Client.clientID", true,
                "Credential", "currentUser", "Client.clientID",
            ],
            "two ands"        => [
                "Credential.currentUser AND Client.clientID AND Client.isDeleted", true,
                "Credential", "currentUser", "Client.clientID AND Client.isDeleted",
            ],
        ];
    }

    /**
     * A join left unnamed, which falls back to the ID of the related Model
     * @param string $relationJoin
     * @param string $ownerJoin
     * @param string $relationField
     * @param string $ownerField
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testAJoinThatIsNotNamedUsesTheIdOfTheRelatedModel(
        string $relationJoin,
        string $ownerJoin,
        string $relationField,
        string $ownerField,
    ): void {
        $relation = new Relation(relationJoin: $relationJoin, ownerJoin: $ownerJoin);
        $relation = self::build($relation);

        $this->assertSame($relationField, $relation->relationFieldName);
        $this->assertSame($ownerField, $relation->ownerFieldName);
        $this->assertNotNull($relation->relationModel);
        $this->assertNotNull($relation->parentModel);
    }

    /**
     * @return array<string,array{string,string,string,string}>
     */
    public static function providerModels(): array {
        return [
            "neither named" => [ "", "", "userID", "userID" ],
            "the relation"  => [ "otherID", "", "otherID", "userID" ],
            "the owner"     => [ "", "currentUser", "userID", "currentUser" ],
            "both of them"  => [ "otherID", "currentUser", "otherID", "currentUser" ],
        ];
    }



    /**
     * The names of the fields the relation carries over
     * @param Relation     $relation
     * @param list<string> $expected
     * @return void
     */
    #[DataProvider("providerFields")]
    public function testTheFieldsAreTakenFromTheRelatedModel(
        Relation $relation,
        array $expected,
    ): void {
        $relation = self::build($relation, self::fullUserModel());

        $this->assertTrue($relation->generateFields());
        $this->assertSame($expected, self::prefixNames($relation));
    }

    /**
     * With no names given every field is taken but the ID and anything the
     * parent already holds, and the timestamps and the deleted flag are only
     * taken when they were asked for
     * @return array<string,array{Relation,list<string>}>
     */
    public static function providerFields(): array {
        return [
            "everything"         => [
                new Relation(),
                [ "userFirstName", "userLastName", "clientID", "userIsActive" ],
            ],
            "some of them"       => [
                new Relation(fieldNames: [ "firstName", "isActive" ]),
                [ "userFirstName", "userIsActive" ],
            ],
            "one it lacks"       => [
                new Relation(fieldNames: [ "notAField" ]),
                [],
            ],
            "without the prefix" => [
                new Relation(fieldNames: [ "firstName" ], withPrefix: false),
                [ "firstName" ],
            ],
            "one without it"     => [
                new Relation(fieldNames: [ "firstName", "isActive" ], withoutPrefix: [ "isActive" ]),
                [ "userFirstName", "isActive" ],
            ],
            "the timestamps"     => [
                new Relation(fieldNames: [ "firstName", "createdTime" ]),
                [ "userFirstName", "userCreatedTime" ],
            ],
            "the deleted flag"   => [
                new Relation(fieldNames: [ "firstName", "isDeleted" ]),
                [ "userFirstName", "userIsDeleted" ],
            ],
            "the flag asked for" => [
                new Relation(withDeleted: true),
                [ "userFirstName", "userLastName", "clientID", "userIsActive", "userIsDeleted" ],
            ],
        ];
    }

    public function testAnIdColumnKeepsItsOwnNameRatherThanThePrefix(): void {
        // A field whose database name differs is an ID of another Model, and
        // the join has to name it the way that Model does
        $relation = self::build(new Relation(fieldNames: [ "clientID" ]), self::fullUserModel());
        $relation->generateFields();

        $this->assertSame([ "clientID" ], self::prefixNames($relation));
    }

    public function testAFieldMarkedAsACodeKeepsItsOwnName(): void {
        $userModel = self::fullUserModel();
        $userModel->fields[1]->isCode = true;

        $relation = self::build(new Relation(fieldNames: [ "firstName" ]), $userModel);
        $relation->generateFields();

        $this->assertSame([ "firstName" ], self::prefixNames($relation));
    }

    public function testAFieldAlreadyStartingWithThePrefixIsNotPrefixedAgain(): void {
        $userModel = self::model("User", [
            Field::create(name: "userID", dbName: "USER_ID", type: FieldType::Number, isID: true),
            Field::create(name: "userName", type: FieldType::String),
        ]);

        $relation = self::build(new Relation(fieldNames: [ "userName" ]), $userModel);
        $relation->generateFields();

        $this->assertSame([ "userName" ], self::prefixNames($relation));
    }

    public function testAFieldTheParentAlreadyHoldsIsNotTakenTwice(): void {
        $parentModel = self::model("Credential", [
            Field::create(name: "userFirstName", type: FieldType::String),
        ]);

        $relation = self::build(new Relation(), self::fullUserModel(), $parentModel);
        $relation->generateFields();

        $this->assertNotContains("userFirstName", self::prefixNames($relation));
    }

    /**
     * A build step asked before the Models were set
     * @param string $method
     * @return void
     */
    #[DataProvider("providerNoModels")]
    public function testAStepThatNeedsTheModelsDoesNothingWithoutThem(string $method): void {
        $relation = new Relation();
        $relation->setDataFromAttribute("User", "user");

        /** @var callable */
        $callable = [ $relation, $method ];
        $this->assertFalse($callable());
    }

    /**
     * @return array<string,array{string}>
     */
    public static function providerNoModels(): array {
        return [
            "the fields" => [ "generateFields" ],
            "the owner"  => [ "inferOwnerModelName" ],
        ];
    }

    public function testTheFieldsAreEmptyWithoutAModel(): void {
        $this->assertSame([], (new Relation())->getFields());
    }

    /**
     * A Relation asked for the fields of the Model it reads
     * @param Relation     $relation
     * @param list<string> $expected
     * @return void
     */
    #[DataProvider("providerRawFields")]
    public function testTheFieldsOfTheModelAreHandedOverWhole(
        Relation $relation,
        array $expected,
    ): void {
        $relation = self::build($relation, self::fullUserModel());
        $result   = [];
        foreach ($relation->getFields() as $field) {
            $result[] = $field->name;
        }

        $this->assertSame($expected, $result);
    }

    /**
     * These are the fields before the prefix and the filtering, so the ID is
     * there too and only the timestamps and the flag are held back
     * @return array<string,array{Relation,list<string>}>
     */
    public static function providerRawFields(): array {
        $base = [ "userID", "firstName", "lastName", "clientID", "isActive" ];
        return [
            "nothing asked for" => [ new Relation(), $base ],
            "the timestamps"    => [
                new Relation(fieldNames: [ "modifiedTime" ]),
                [ ...$base, "createdTime" ],
            ],
            "the deleted flag"  => [
                new Relation(withDeleted: true),
                [ ...$base, "isDeleted" ],
            ],
            "the flag by name"  => [
                new Relation(fieldNames: [ "isDeleted" ]),
                [ ...$base, "isDeleted" ],
            ],
        ];
    }

    public function testARelationLooksPastItselfAndPastTheOnesWithNoModel(): void {
        $parentModel = self::model("Credential");
        $relation    = new Relation(ownerJoin: "notAField");
        $unbuilt     = new Relation();

        $parentModel->relations = [ $relation, $unbuilt ];
        self::build($relation, self::userModel(), $parentModel);

        $this->assertFalse($relation->inferOwnerModelName());
        $this->assertSame("", $relation->ownerModelName);
    }



    public function testTheOwnerIsTheParentWhenItHoldsTheKey(): void {
        $parentModel = self::model("Credential", [
            Field::create(name: "currentUser", type: FieldType::Number),
        ]);

        $relation = self::build(new Relation(ownerJoin: "currentUser"), self::userModel(), $parentModel);

        $this->assertTrue($relation->inferOwnerModelName());
        $this->assertSame("Credential", $relation->ownerModelName);
    }

    public function testTheOwnerIsAnotherRelationWhenItHoldsTheKey(): void {
        // The join hangs off a table already joined, rather than off the parent
        $clientModel = self::model("Client", [
            Field::create(name: "clientID", dbName: "CLIENT_ID", type: FieldType::Number, isID: true),
            Field::create(name: "ownerUser", type: FieldType::Number),
        ]);
        $other       = self::build(new Relation(), $clientModel);
        $parentModel = self::model("Credential");
        $parentModel->relations = [ $other ];

        $relation = self::build(new Relation(ownerJoin: "ownerUser"), self::userModel(), $parentModel);

        $this->assertTrue($relation->inferOwnerModelName());
        $this->assertSame("Client", $relation->ownerModelName);
    }

    public function testAnOwnerNamedInTheJoinIsNotInferredAgain(): void {
        $relation = self::build(new Relation(ownerJoin: "Client.clientID"));

        $this->assertFalse($relation->inferOwnerModelName());
        $this->assertSame("Client", $relation->ownerModelName);
    }

    public function testAnOwnerThatIsNowhereIsLeftUnnamed(): void {
        $relation = self::build(new Relation(ownerJoin: "notAField"));

        $this->assertFalse($relation->inferOwnerModelName());
        $this->assertSame("", $relation->ownerModelName);
    }

    /**
     * The database names of the two sides of the join
     * @param array<string,string> $dbNames
     * @param string               $relationField
     * @param string               $ownerField
     * @return void
     */
    #[DataProvider("providerDbNames")]
    public function testTheJoinTakesTheDatabaseNamesWhenThereAreAny(
        array $dbNames,
        string $relationField,
        string $ownerField,
    ): void {
        $relation = self::build(new Relation(relationJoin: "userID", ownerJoin: "currentUser"));
        $relation->setDbNames($dbNames);

        $this->assertSame($relationField, $relation->relationFieldDbName);
        $this->assertSame($ownerField, $relation->ownerFieldDbName);
    }

    /**
     * A name that is not an ID of a Model is not in the map, and is used as it
     * was written
     * @return array<string,array{array<string,string>,string,string}>
     */
    public static function providerDbNames(): array {
        return [
            "neither of them" => [ [], "userID", "currentUser" ],
            "the relation"    => [ [ "userID" => "USER_ID" ], "USER_ID", "currentUser" ],
            "both of them"    => [
                [ "userID" => "USER_ID", "currentUser" => "CURRENT_USER" ],
                "USER_ID", "CURRENT_USER",
            ],
        ];
    }



    /**
     * The Model the join reads from, and the table it names
     * @param string $modelName
     * @param string $aliasName
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerTableName")]
    public function testTheTableIsTheAliasWhenThereIsOne(
        string $modelName,
        string $aliasName,
        string $expected,
    ): void {
        $relation = Relation::create($modelName, $aliasName, "", "", "", "", []);

        $this->assertSame($expected, $relation->getDbTableName());
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function providerTableName(): array {
        return [
            "no alias"  => [ "User", "", "user" ],
            "an alias"  => [ "User", "Owner", "owner" ],
            "two words" => [ "CrateItem", "", "crate_item" ],
        ];
    }

    /**
     * The extra condition of the join, and what is read out of it
     * @param string       $andQuery
     * @param string       $modelName
     * @param string       $tableName
     * @param list<string> $fieldNames
     * @param string       $value
     * @param bool         $isDeleted
     * @return void
     */
    #[DataProvider("providerAnd")]
    public function testTheExtraConditionIsReadOutOfTheOwnerJoin(
        string $andQuery,
        string $modelName,
        string $tableName,
        array $fieldNames,
        string $value,
        bool $isDeleted,
    ): void {
        $relation = Relation::create("User", "", "", "", "", $andQuery, []);

        $this->assertSame($modelName, $relation->getAndModelName());
        $this->assertSame($tableName, $relation->getAndTableName());
        $this->assertSame($fieldNames, $relation->getAndFieldNames());
        $this->assertSame($value, $relation->getAndValue());
        $this->assertSame($isDeleted, $relation->getAndIsDeleted());
    }

    /**
     * @return array<string,array{string,string,string,list<string>,string,bool}>
     */
    public static function providerAnd(): array {
        return [
            "nothing given"  => [ "", "", "", [], "", false ],
            "a field"        => [ "Client.clientID", "Client", "client", [ "clientID" ], "", false ],
            "no model"       => [ "clientID", "", "", [], "", false ],
            "two fields"     => [
                "Client.clientID AND Client.storeID", "Client", "client",
                [ "clientID", "storeID" ], "", false,
            ],
            "a deleted flag" => [ "Client.isDeleted", "Client", "client", [ "isDeleted" ], "", true ],
            "a value"        => [
                "Client.clientID AND status = ?", "Client", "client",
                [ "clientID" ], "status", false,
            ],
        ];
    }

    /**
     * A Relation, and the join the query is given
     * @param Relation $relation
     * @param string   $expected
     * @return void
     */
    #[DataProvider("providerExpression")]
    public function testTheJoinIsWrittenFromBothSides(Relation $relation, string $expected): void {
        $this->assertSame($expected, $relation->getExpression());
    }

    /**
     * @return array<string,array{Relation,string}>
     */
    public static function providerExpression(): array {
        return [
            "a plain join"     => [
                Relation::create("User", "", "USER_ID", "Credential", "CURRENT_USER", "", []),
                "LEFT JOIN `user` ON (user.USER_ID = credential.CURRENT_USER)",
            ],
            "an aliased one"   => [
                Relation::create("User", "Owner", "USER_ID", "Credential", "CURRENT_USER", "", []),
                "LEFT JOIN `user` AS owner ON (owner.USER_ID = credential.CURRENT_USER)",
            ],
            "one with a field" => [
                Relation::create("User", "", "USER_ID", "Credential", "CURRENT_USER", "Client.clientID", []),
                "LEFT JOIN `user` ON (user.USER_ID = credential.CURRENT_USER" .
                " AND user.CLIENT_ID = client.CLIENT_ID)",
            ],
            "one with a flag"  => [
                Relation::create("User", "", "USER_ID", "Credential", "CURRENT_USER", "isDeleted", []),
                "LEFT JOIN `user` ON (user.USER_ID = credential.CURRENT_USER AND user.isDeleted = 0)",
            ],
            "one with a value" => [
                Relation::create(
                    "User", "", "USER_ID", "Credential", "CURRENT_USER",
                    "Client.clientID AND status = ?", [],
                ),
                "LEFT JOIN `user` ON (user.USER_ID = credential.CURRENT_USER" .
                " AND user.CLIENT_ID = client.CLIENT_ID AND user.status = ?)",
            ],
        ];
    }



    public function testTheValuesOfEveryFieldAreReadFromTheRow(): void {
        $relation = Relation::create("User", "", "", "", "", "", [
            Field::create(name: "firstName", prefixName: "userFirstName", type: FieldType::String),
            Field::create(name: "age", prefixName: "userAge", type: FieldType::Number),
        ]);

        $this->assertSame([
            "userFirstName" => "Ana",
            "userAge"       => 12,
        ], $relation->toValues([ "userFirstName" => "Ana", "userAge" => "12" ]));
    }

    public function testTheBuildDataLeavesOutWhatIsAlreadyTheDefault(): void {
        $relation = Relation::create("User", "Owner", "USER_ID", "Credential", "CURRENT_USER", "a", [
            Field::create(name: "firstName", prefixName: "userFirstName", type: FieldType::String),
        ]);

        $this->assertSame([
            "relationModelName"   => "User",
            "relationAliasName"   => "Owner",
            "relationFieldDbName" => "USER_ID",
            "ownerModelName"      => "Credential",
            "ownerFieldDbName"    => "CURRENT_USER",
            "ownerAndQuery"       => "a",
            "fields"              => [
                [
                    "name"       => "firstName",
                    "dbName"     => "firstName",
                    "prefixName" => "userFirstName",
                    "type"       => FieldType::String,
                ],
            ],
        ], $relation->toBuildData());
    }

    public function testTheBuildDataCarriesWhatIsNotTheDefault(): void {
        $relation = Relation::create("User", "", "", "", "", "", [
            Field::create(name: "avatar", type: FieldType::File, decimals: 3, filePath: "users"),
        ]);

        $result = $relation->toBuildData();
        $this->assertSame(3, $result["fields"][0]["decimals"]);
        $this->assertSame("users", $result["fields"][0]["filePath"]);
    }



    /**
     * The names already taken by the other relations, and the one left for this
     * @param list<string> $otherNames
     * @param string       $expected
     * @return void
     */
    #[DataProvider("providerName")]
    public function testTheNameIsTheFirstOneNotAlreadyTaken(
        array $otherNames,
        string $expected,
    ): void {
        $relation = self::build(new Relation(ownerJoin: "clientID", prefix: "made"));

        $this->assertSame($expected, $relation->getName($otherNames));
    }

    /**
     * The ID of the related Model comes first, then the owner column, then the
     * prefix, then the name of the property
     * @return array<string,array{list<string>,string}>
     */
    public static function providerName(): array {
        return [
            "nothing taken"  => [ [], "USER_ID" ],
            "the id"         => [ [ "USER_ID" ], "CLIENT_ID" ],
            "the column too" => [ [ "USER_ID", "CLIENT_ID" ], "made" ],
            "the prefix too" => [ [ "USER_ID", "CLIENT_ID", "made" ], "user" ],
        ];
    }

    public function testARelationWithNoModelHasNoName(): void {
        $this->assertSame("", (new Relation())->getName([]));
    }

    public function testTheSchemaJsonNamesTheForeignKey(): void {
        $parentModel = self::model("Credential", [
            Field::create(name: "currentUser", type: FieldType::Number),
        ]);
        $relation = self::build(new Relation(ownerJoin: "currentUser"), self::userModel(), $parentModel);
        $relation->inferOwnerModelName();
        $relation->setDbNames([ "userID" => "USER_ID" ]);

        $this->assertSame([
            "fromField" => "currentUser",
            "toTable"   => "user",
            "toField"   => "USER_ID",
        ], $relation->toSchemaJSON("currentUser"));
    }

    /**
     * A Relation the published schema has no foreign key for
     * @param bool $withModels
     * @return void
     */
    #[DataProvider("providerNoSchemaJSON")]
    public function testAJoinThatIsNotOffTheParentIsNoForeignKey(bool $withModels): void {
        // The key only belongs in the schema when the parent is the one holding it
        $relation = new Relation(ownerJoin: "Client.clientID");
        if ($withModels) {
            $relation = self::build($relation);
        }

        $this->assertSame([], $relation->toSchemaJSON("currentUser"));
    }

    /**
     * @return array<string,array{bool}>
     */
    public static function providerNoSchemaJSON(): array {
        return [
            "no models at all" => [ false ],
            "another owner"    => [ true ],
        ];
    }



    /**
     * Returns a User Model with a field of every shape the relation cares for
     * @return SchemaModel
     */
    private static function fullUserModel(): SchemaModel {
        return self::model("User", [
            Field::create(name: "userID", dbName: "USER_ID", type: FieldType::Number, isID: true),
            Field::create(name: "firstName", type: FieldType::String),
            Field::create(name: "lastName", type: FieldType::String),
            Field::create(name: "clientID", dbName: "CLIENT_ID", type: FieldType::Number),
            Field::create(name: "isActive", type: FieldType::Boolean),
            Field::create(name: "createdTime", type: FieldType::Date),
            Field::create(name: "isDeleted", type: FieldType::Boolean),
        ]);
    }

    /**
     * Returns the name each field of the Relation is carried over as
     * @param Relation $relation
     * @return list<string>
     */
    private static function prefixNames(Relation $relation): array {
        $result = [];
        foreach ($relation->fields as $field) {
            $result[] = $field->prefixName;
        }
        return $result;
    }
}
