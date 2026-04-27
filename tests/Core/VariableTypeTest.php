<?php
namespace Tests\Core;

use Framework\Core\VariableType;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class VariableTypeTest extends TestCase {

    #[DataProvider("providerGet")]
    public function testGet(mixed $value, bool $useLists, VariableType $expected): void {
        $this->assertSame($expected, VariableType::get($value, $useLists));
    }

    public static function providerGet(): array {
        return [
            "assoc_array" => [ [ "a" => 1 ], false, VariableType::Array ],
            "list_array"  => [ [ "a", "b" ], true, VariableType::List ],
            "bool"        => [ true, false, VariableType::Boolean ],
            "int"         => [ 10, false, VariableType::Integer ],
            "float"       => [ 10.5, false, VariableType::Float ],
            "string"      => [ "test", false, VariableType::String ],
            "object"      => [ new stdClass(), false, VariableType::String ],
        ];
    }


    #[DataProvider("providerGetType")]
    public function testGetType(VariableType $type, string $expected): void {
        $this->assertSame($expected, VariableType::getType($type));
    }

    public static function providerGetType(): array {
        return [
            "array"   => [ VariableType::Array, "array" ],
            "list"    => [ VariableType::List, "array" ],
            "bool"    => [ VariableType::Boolean, "bool" ],
            "int"     => [ VariableType::Integer, "int" ],
            "float"   => [ VariableType::Float, "float" ],
            "string"  => [ VariableType::String, "string" ],
            "default" => [ VariableType::None, "string" ],
        ];
    }


    #[DataProvider("providerGetDocType")]
    public function testGetDocType(VariableType $type, string $expected): void {
        $this->assertSame($expected, VariableType::getDocType($type));
    }

    public static function providerGetDocType(): array {
        return [
            "array"   => [ VariableType::Array, "array<int|stringeger,mixed>" ],
            "list"    => [ VariableType::List, "list<string>" ],
            "bool"    => [ VariableType::Boolean, "bool" ],
            "int"     => [ VariableType::Integer, "int" ],
            "float"   => [ VariableType::Float, "float" ],
            "string"  => [ VariableType::String, "string" ],
            "default" => [ VariableType::None, "string" ],
        ];
    }


    #[DataProvider("providerEncodeValue")]
    public function testEncodeValue(VariableType $type, mixed $value, string $expected): void {
        $this->assertSame($expected, VariableType::encodeValue($type, $value));
    }

    public static function providerEncodeValue(): array {
        return [
            "array_empty"    => [ VariableType::Array, [], "{}" ],
            "array_json"     => [ VariableType::Array, [ "a" => 1 ], '{"a":1}' ],
            "bool_empty"     => [ VariableType::Boolean, "", "0" ],
            "bool_true"      => [ VariableType::Boolean, true, "1" ],
            "int_empty"      => [ VariableType::Integer, null, "0" ],
            "int_value"      => [ VariableType::Integer, 42, "42" ],
            "float_value"    => [ VariableType::Float, 1.5, "1.5" ],
            "string_value"   => [ VariableType::String, "hello", "hello" ],
            "string_invalid" => [ VariableType::String, new stdClass(), "" ],
        ];
    }
}
