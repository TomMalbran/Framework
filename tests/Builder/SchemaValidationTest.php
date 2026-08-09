<?php
namespace Tests\Builder;

use Framework\Builder\Builder;
use Framework\Database\Builder\SchemaCode;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Validate;
use Framework\Database\SchemaModel;
use Framework\Database\Type\ValidateType;

use PHPUnit\Framework\TestCase;

use ReflectionMethod;

class SchemaValidationTest extends TestCase {

    public static function setUpBeforeClass(): void {
        $method = new ReflectionMethod(Builder::class, "loadTemplates");
        $method->invoke(null);
    }

    /**
     * Builds a model whose fields between them reach every validation type
     *
     * The framework's own models only use a few of them, so the generated
     * validation stays mostly unwritten unless a model like this asks for it.
     * @return SchemaModel
     */
    private function everyValidation(): SchemaModel {
        $fields = [
            Field::create(name: "crateID", type: FieldType::Number, isID: true),
            Field::create(name: "name", type: FieldType::String),
            Field::create(name: "email", type: FieldType::String),
            Field::create(name: "website", type: FieldType::String),
            Field::create(name: "amount", type: FieldType::Number),
            Field::create(name: "price", type: FieldType::Number),
            Field::create(name: "startDate", type: FieldType::Date),
            Field::create(name: "tags", type: FieldType::JSON),
        ];

        // These two are set by the attribute rather than by create()
        $fields[1]->isUnique  = true;
        $fields[6]->dateInput = "startHour";

        $validates = [
            (new Validate(isRequired: true))->setField($fields[1], "Crate"),
            (new Validate(isEmail: true))->setField($fields[2], "Crate"),
            (new Validate(isUrl: true))->setField($fields[3], "Crate"),
            (new Validate(isNumeric: true, minValue: 1, maxValue: 10))->setField($fields[4], "Crate"),
            (new Validate(isPrice: true))->setField($fields[5], "Crate"),
            (new Validate())->setField($fields[6], "Crate"),
            (new Validate())->setField($fields[7], "Crate"),
        ];

        return new SchemaModel(
            name:        "Crate",
            description: "Reaches every validation branch",
            namespace:   "Tests\\Builder\\Generated",
            canCreate:   true,
            canEdit:     true,
            mainFields:  $fields,
            validates:   $validates,
        );
    }



    public function testTheValidationTypesAreWhatTheFieldsImply(): void {
        $types = [];
        foreach ($this->everyValidation()->validates as $validate) {
            $types[] = $validate->type;
        }

        $this->assertContains(ValidateType::String, $types);
        $this->assertContains(ValidateType::Email, $types);
        $this->assertContains(ValidateType::Url, $types);
        $this->assertContains(ValidateType::Number, $types);
        $this->assertContains(ValidateType::Price, $types);
        $this->assertContains(ValidateType::Date, $types);
        $this->assertContains(ValidateType::List, $types);
    }

    public function testEveryValidationReachesTheGeneratedSchema(): void {
        $code = SchemaCode::getCode($this->everyValidation());

        $this->assertNotSame("", $code);

        $error = null;
        try {
            token_get_all($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $error = $e->getMessage();
        }
        $this->assertNull($error, "the generated php does not parse: $error");

        // Each validated field has to be named in what comes out
        foreach ([ "name", "email", "website", "amount", "price", "startDate", "tags" ] as $field) {
            $this->assertStringContainsString($field, $code, "$field is missing from the schema");
        }
    }

    public function testTheUniqueFieldIsCarriedThrough(): void {
        $code = SchemaCode::getCode($this->everyValidation());

        $this->assertStringContainsString("name", $code);
        $this->assertStringContainsString("class CrateSchema", $code);
    }
}
