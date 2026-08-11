<?php
namespace Tests\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Requested;
use Framework\Database\Model\Validate;
use Framework\Database\Type\ValidateType;
use Framework\Utils\Color;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Validate Attribute, which says how a Request field is checked
 */
class ValidateTest extends TestCase {

    /**
     * Returns a Validate already given the Field it sits on
     * @param Validate  $validate
     * @param FieldType $type      Optional.
     * @param string    $name      Optional.
     * @param string    $dateInput Optional.
     * @return Validate
     */
    private function withField(
        Validate $validate,
        FieldType $type = FieldType::String,
        string $name = "name",
        string $dateInput = "",
    ): Validate {
        $field = Field::create(name: $name, type: $type);
        $field->dateInput = $dateInput;

        return $validate->setField($field, "Crate");
    }

    /**
     * Returns a Requested field of the given name
     * @param string $name
     * @return Requested
     */
    private function requested(string $name): Requested {
        $result = new Requested();
        $result->name = $name;
        return $result;
    }



    /**
     * What the attribute was given, and the method it validates through
     * @param string $typeOf
     * @param string $belongsTo
     * @param string $method
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerMethod")]
    public function testTheMethodFallsBackToTheOneTheClassOffers(
        string $typeOf,
        string $belongsTo,
        string $method,
        string $expected,
    ): void {
        /** @var class-string|null */
        $typeOfClass = $typeOf !== "" ? $typeOf : null;
        /** @var class-string|null */
        $belongsClass = $belongsTo !== "" ? $belongsTo : null;

        $validate = new Validate(
            typeOf:    $typeOfClass,
            belongsTo: $belongsClass,
            method:    $method,
        );

        $this->assertSame($expected, $validate->method);
    }

    /**
     * A type is asked whether the value is one of its own, and a Model is
     * asked whether the row is there
     * @return array<string,array{string,string,string,string}>
     */
    public static function providerMethod(): array {
        return [
            "nothing given"    => [ "", "", "", "" ],
            "a type"           => [ "App\\Color", "", "", "isValid" ],
            "a model"          => [ "", "App\\CrateModel", "", "exists" ],
            "both of them"     => [ "App\\Color", "App\\CrateModel", "", "isValid" ],
            "one of its own"   => [ "App\\Color", "", "check", "check" ],
            "one with a model" => [ "", "App\\CrateModel", "check", "check" ],
        ];
    }

    /**
     * A prefix given to the attribute, and the one the errors are named with
     * @param string $given
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerPrefix")]
    public function testThePrefixesAreNamedInConstantCase(
        string $given,
        string $expected,
    ): void {
        $validate = new Validate(prefix: $given, belongsName: $given);

        $this->assertSame($expected, $validate->prefix);
        $this->assertSame($expected, $validate->belongsName);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerPrefix(): array {
        return [
            "nothing given"   => [ "", "" ],
            "one word"        => [ "crate", "CRATE" ],
            "two words"       => [ "crateItem", "CRATE_ITEM" ],
            "already in caps" => [ "CRATE", "CRATE" ],
        ];
    }



    /**
     * A Field and its Validate, and the type the value is checked as
     * @param Validate     $validate
     * @param FieldType    $type
     * @param string       $dateInput
     * @param ValidateType $expected
     * @return void
     */
    #[DataProvider("providerType")]
    public function testTheTypeIsTakenFromTheFieldAndTheFlags(
        Validate $validate,
        FieldType $type,
        string $dateInput,
        ValidateType $expected,
    ): void {
        $result = $this->withField($validate, type: $type, dateInput: $dateInput);

        $this->assertSame($expected, $result->type);
        $this->assertSame($type, $result->fieldType);
    }

    /**
     * The flags are asked first and in order, so a field marked as an email
     * is one whatever column it was declared as
     * @return array<string,array{Validate,FieldType,string,ValidateType}>
     */
    public static function providerType(): array {
        return [
            "an email"       => [ new Validate(isEmail: true), FieldType::String, "", ValidateType::Email ],
            "a url"          => [ new Validate(isUrl: true), FieldType::String, "", ValidateType::Url ],
            "a price"        => [ new Validate(isPrice: true), FieldType::Number, "", ValidateType::Price ],
            "an email first" => [ new Validate(isEmail: true, isUrl: true), FieldType::Number, "", ValidateType::Email ],
            "a date"         => [ new Validate(), FieldType::Number, "sentDate", ValidateType::Date ],
            "a number"       => [ new Validate(), FieldType::Number, "", ValidateType::Number ],
            "a float"        => [ new Validate(), FieldType::Float, "", ValidateType::Number ],
            "a numeric one"  => [ new Validate(isNumeric: true), FieldType::String, "", ValidateType::Number ],
            "an enum"        => [ new Validate(), FieldType::Enum, "", ValidateType::Enum ],
            "a string"       => [ new Validate(), FieldType::String, "", ValidateType::String ],
            "a text"         => [ new Validate(), FieldType::Text, "", ValidateType::String ],
            "a long text"    => [ new Validate(), FieldType::LongText, "", ValidateType::String ],
            "a json"         => [ new Validate(), FieldType::JSON, "", ValidateType::List ],
            "a boolean"      => [ new Validate(), FieldType::Boolean, "", ValidateType::None ],
            "a timestamp"    => [ new Validate(), FieldType::Date, "", ValidateType::None ],
        ];
    }

    public function testTheFieldHandsOverWhatTheValidateNeeds(): void {
        $field = Field::create(name: "sentTime", type: FieldType::Number);
        $field->isUnique  = true;
        $field->dateInput = "sentDate";
        $field->hourInput = "sentHour";

        $validate = (new Validate())->setField($field, "Crate Item");

        $this->assertSame("sentTime", $validate->name);
        $this->assertTrue($validate->isUnique);
        $this->assertSame("sentDate", $validate->dateInput);
        $this->assertSame("sentHour", $validate->hourInput);
        $this->assertSame("CRATE_ITEM", $validate->prefix);
    }

    public function testAPrefixOfItsOwnIsNotReplacedByTheModel(): void {
        $validate = $this->withField(new Validate(prefix: "box"));

        $this->assertSame("BOX", $validate->prefix);
    }

    public function testAnEnumIsValidatedAgainstTheEnumOfTheField(): void {
        $field = Field::create(name: "kind", type: FieldType::Enum);
        $field->enumClass = "App\\Schema\\CrateKind";

        $validate = (new Validate())->setField($field, "Crate");

        $this->assertSame("App\\Schema\\CrateKind", $validate->typeOf);
        $this->assertSame("isValid", $validate->method);
        $this->assertTrue($validate->shouldValidate());
    }

    public function testTheStatusIsValidatedAsOne(): void {
        $validate = (new Validate())->setStatus();

        $this->assertSame("status", $validate->name);
        $this->assertSame(ValidateType::Status, $validate->type);
    }



    /**
     * A Validate, and whether the generated code checks it at all
     * @param Validate  $validate
     * @param FieldType $type
     * @param bool      $expected
     * @return void
     */
    #[DataProvider("providerShouldValidate")]
    public function testOnlyAValidateWithSomethingToSayIsWritten(
        Validate $validate,
        FieldType $type,
        bool $expected,
    ): void {
        $this->assertSame($expected, $this->withField($validate, type: $type)->shouldValidate());
    }

    /**
     * A string or an enum is only worth checking when something was asked of
     * it, while a number or a date is always worth checking. An enum takes
     * its class from the Field, so one built without it has nothing to ask
     * @return array<string,array{Validate,FieldType,bool}>
     */
    public static function providerShouldValidate(): array {
        return [
            "nothing to check"      => [ new Validate(), FieldType::Boolean, false ],
            "a plain string"        => [ new Validate(), FieldType::String, false ],
            "a required string"     => [ new Validate(isRequired: true), FieldType::String, true ],
            "a checked string"      => [ new Validate(typeOf: "App\\Color"), FieldType::String, true ],
            "a bounded string"      => [ new Validate(maxLength: 10), FieldType::String, true ],
            "an enum with no class" => [ new Validate(), FieldType::Enum, false ],
            "a plain list"          => [ new Validate(), FieldType::JSON, false ],
            "a checked list"        => [ new Validate(typeOf: "App\\Color"), FieldType::JSON, true ],
            "an owned list"         => [ new Validate(belongsTo: "App\\CrateModel"), FieldType::JSON, true ],
            "a number"              => [ new Validate(), FieldType::Number, true ],
            "an email"              => [ new Validate(isEmail: true), FieldType::String, true ],
        ];
    }

    public function testAUniqueStringIsAlwaysChecked(): void {
        $field = Field::create(name: "name", type: FieldType::String);
        $field->isUnique = true;

        $this->assertTrue((new Validate())->setField($field, "Crate")->shouldValidate());
    }



    /**
     * The name of the field, and the error the generated code reports
     * @param string $name
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerFieldError")]
    public function testTheErrorIsNamedAfterTheFieldWithoutItsId(
        string $name,
        string $expected,
    ): void {
        $validate = $this->withField(new Validate(prefix: "crate"), name: $name);

        $this->assertSame($expected, $validate->getFieldError());
        $this->assertSame("{$expected}_INVALID", $validate->getTypeInvalidError());
        $this->assertSame($expected, $validate->getTypeInvalidError(withSuffix: false));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerFieldError(): array {
        return [
            "one word"  => [ "name", "CRATE_ERROR_NAME" ],
            "two words" => [ "firstName", "CRATE_ERROR_FIRST_NAME" ],
            "an id"     => [ "boxID", "CRATE_ERROR_BOX" ],
            "nothing"   => [ "", "CRATE_ERROR_" ],
        ];
    }

    public function testAColorReportsTheErrorEveryColorShares(): void {
        $validate = $this->withField(new Validate(prefix: "crate", typeOf: Color::class));

        $this->assertSame("GENERAL_ERROR_COLOR", $validate->getTypeInvalidError());
        $this->assertSame("GENERAL_ERROR_COLOR", $validate->getTypeInvalidError(withSuffix: false));
    }

    /**
     * The class the value is checked against, and the error it reports
     * @param string $typeOf
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerTypeOfError")]
    public function testTheTypeReportsTheErrorOfItsOwnName(
        string $typeOf,
        string $expected,
    ): void {
        /** @var class-string */
        $typeOfClass = $typeOf;

        $this->assertSame($expected, (new Validate(typeOf: $typeOfClass))->getTypeOfError());
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerTypeOfError(): array {
        return [
            "a class"      => [ "App\\Schema\\CrateKind", "CRATE_KIND_ERROR_EXISTS" ],
            "no namespace" => [ "CrateKind", "CRATE_KIND_ERROR_EXISTS" ],
        ];
    }

    /**
     * The Model the value belongs to, and the error it reports
     * @param string $belongsTo
     * @param string $belongsName
     * @param bool   $forList
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerBelongsToError")]
    public function testTheOwnerReportsTheErrorOfItsOwnName(
        string $belongsTo,
        string $belongsName,
        bool $forList,
        string $expected,
    ): void {
        /** @var class-string */
        $belongsClass = $belongsTo;
        $validate     = new Validate(belongsTo: $belongsClass, belongsName: $belongsName);

        $this->assertSame($expected, $validate->getBelongsToError(forList: $forList));
    }

    /**
     * A list reports that some of its values are missing rather than one
     * @return array<string,array{string,string,bool,string}>
     */
    public static function providerBelongsToError(): array {
        return [
            "a model"        => [ "App\\Schema\\CrateModel", "", false, "CRATE_MODEL_ERROR_EXISTS" ],
            "a list of them" => [ "App\\Schema\\CrateModel", "", true, "CRATE_MODEL_ERROR_SOME_EXISTS" ],
            "a name given"   => [ "App\\Schema\\CrateModel", "box", false, "BOX_ERROR_EXISTS" ],
            "no namespace"   => [ "Crate", "", false, "CRATE_ERROR_EXISTS" ],
        ];
    }

    /**
     * The name of the date field, and the error it reports
     * @param string $name
     * @param string $date
     * @param string $hour
     * @return void
     */
    #[DataProvider("providerDateError")]
    public function testARangeOfDatesReportsWhichEndIsWrong(
        string $name,
        string $date,
        string $hour,
    ): void {
        $validate = $this->withField(new Validate(), name: $name);

        $this->assertSame($date, $validate->getDateError());
        $this->assertSame($hour, $validate->getHourError());
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function providerDateError(): array {
        return [
            "the start"   => [ "fromDate", "GENERAL_ERROR_FROM_DATE", "GENERAL_ERROR_FROM_HOUR" ],
            "the end"     => [ "toDate", "GENERAL_ERROR_TO_DATE", "GENERAL_ERROR_TO_HOUR" ],
            "a plain one" => [ "sentDate", "GENERAL_ERROR_DATE", "GENERAL_ERROR_HOUR" ],
        ];
    }



    /**
     * A Validate over a number, and the bounds handed to the check
     * @param Validate $validate
     * @param string   $expected
     * @return void
     */
    #[DataProvider("providerNumericParams")]
    public function testTheBoundsAreOnlyPassedWhenTheySaySomething(
        Validate $validate,
        string $expected,
    ): void {
        $this->assertSame($expected, $validate->getNumericParams());
    }

    /**
     * A signed number is given a null minimum so that it is allowed to be
     * negative, and a required number with no bounds is given nothing
     * @return array<string,array{Validate,string}>
     */
    public static function providerNumericParams(): array {
        return [
            "a required number" => [ new Validate(isRequired: true), "" ],
            "an optional one"   => [ new Validate(), ", 0" ],
            "a signed one"      => [ new Validate(isRequired: true, isSigned: true), ", null" ],
            "a signed maximum"  => [
                new Validate(isRequired: true, isSigned: true, maxValue: 10),
                ", null, 10",
            ],
            "a minimum"         => [ new Validate(isRequired: true, minValue: 5), ", 5" ],
            "a maximum"         => [ new Validate(isRequired: true, maxValue: 10), ", 0, 10" ],
            "both of them"      => [ new Validate(isRequired: true, minValue: 5, maxValue: 10), ", 5, 10" ],
            "a signed minimum"  => [
                new Validate(isRequired: true, isSigned: true, minValue: -5),
                ", -5",
            ],
        ];
    }



    /**
     * The condition written on the attribute, and the php it becomes
     * @param string $if
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerCondition")]
    public function testTheConditionIsWrittenAgainstTheRequest(
        string $if,
        string $expected,
    ): void {
        $validate = new Validate(if: $if);
        $result   = $validate->createCondition([ $this->requested("kind") ], []);

        $this->assertSame($expected, $result);
    }

    /**
     * A value that is not a number and not a boolean is quoted, and an
     * operator the attribute does not know is read as an equality
     * @return array<string,array{string,string}>
     */
    public static function providerCondition(): array {
        return [
            "nothing given"     => [ "", "" ],
            "half a condition"  => [ "kind =", "" ],
            "too many parts"    => [ "kind = 1 = 2", "" ],
            "a field it lacks"  => [ "color = 1", "" ],
            "a number"          => [ "kind = 1", 'if ($request->kind === 1) {' ],
            "a quoted string"   => [ "kind = 'box'", 'if ($request->kind === "box") {' ],
            "a double quote"    => [ 'kind = "box"', 'if ($request->kind === "box") {' ],
            "a plain string"    => [ "kind = box", 'if ($request->kind === "box") {' ],
            "a boolean"         => [ "kind = true", 'if ($request->kind === true) {' ],
            "a false one"       => [ "kind = false", 'if ($request->kind === false) {' ],
            "an equality"       => [ "kind == 1", 'if ($request->kind === 1) {' ],
            "a strict equality" => [ "kind === 1", 'if ($request->kind === 1) {' ],
            "an inequality"     => [ "kind != 1", 'if ($request->kind !== 1) {' ],
            "a strict one"      => [ "kind !== 1", 'if ($request->kind !== 1) {' ],
            "the other one"     => [ "kind <> 1", 'if ($request->kind !== 1) {' ],
            "one it lacks"      => [ "kind ~ 1", 'if ($request->kind === 1) {' ],
        ];
    }

    public function testAConditionOverAParentIsWrittenAsAVariable(): void {
        // A parent is already a variable in the generated function, while a
        // requested field has to be read off the request
        $validate = new Validate(if: "crateID = 1");
        $result   = $validate->createCondition([], [ [ "fieldName" => "crateID" ] ]);

        $this->assertSame('if ($crateID === 1) {', $result);
    }

    public function testTheParentIsPreferredOverTheRequestedField(): void {
        $validate = new Validate(if: "kind = 1");
        $result   = $validate->createCondition(
            [ $this->requested("kind") ],
            [ [ "fieldName" => "kind" ] ],
        );

        $this->assertSame('if ($kind === 1) {', $result);
    }
}
