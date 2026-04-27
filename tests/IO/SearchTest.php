<?php
namespace Tests\IO;

use Framework\IO\Search;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchTest extends TestCase {

    #[DataProvider("providerConstruct")]
    public function testConstruct(int|string $id, string $title, ?Dictionary $data, int $expectedId, string $expectedTitle, bool $shouldReuseData): void {
        $search = new Search($id, $title, $data);

        $this->assertSame($expectedId, $search->id);
        $this->assertSame($expectedTitle, $search->title);
        $this->assertIsInt($search->id);

        if ($shouldReuseData) {
            $this->assertSame($data, $search->data);
        } else {
            $this->assertInstanceOf(Dictionary::class, $search->data);
            $this->assertNotSame($data, $search->data);
            $this->assertTrue($search->data->isEmpty());
        }
    }

    public static function providerConstruct(): array {
        $dictionary = new Dictionary([ "k" => "v" ]);

        return [
            "integer_id" => [ 10, "Title", $dictionary, 10, "Title", true ],
            "numeric_string_id" => [ "20", "T2", null, 20, "T2", false ],
            "non_numeric_string_id" => [ "nope", "T3", null, 0, "T3", false ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(Search $search, string $key, string $expected): void {
        $this->assertSame($expected, $search->getString($key));
    }

    public static function providerGetString(): array {
        $search = new Search(1, "MyTitle", new Dictionary([ "field" => "value", "num" => 5 ]));

        return [
            "title_property" => [ $search, "title", "MyTitle" ],
            "id_property"    => [ $search, "id", "1" ],
            "data_field"     => [ $search, "field", "value" ],
            "data_number"    => [ $search, "num", "5" ],
            "missing_key"    => [ $search, "missing", "" ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(Search $search, string $key, int $expected): void {
        $this->assertSame($expected, $search->getInt($key));
    }

    public static function providerGetInt(): array {
        $search = new Search("2", "T", new Dictionary([ "a" => "3", "b" => 7, "bad" => [ 1, 2 ]]));

        return [
            "id_property"    => [ $search, "id", 2 ],
            "title_property" => [ $search, "title", 0 ],
            "data_a"         => [ $search, "a", 3 ],
            "data_b"         => [ $search, "b", 7 ],
            "array_value"    => [ $search, "bad", 0 ],
            "missing_key"    => [ $search, "missing", 0 ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(Search $search, array $expected): void {
        $serialized = $search->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey("id", $serialized);
        $this->assertArrayHasKey("title", $serialized);
        $this->assertArrayHasKey("data", $serialized);
        $this->assertSame($expected["id"], $serialized["id"]);
        $this->assertSame($expected["title"], $serialized["title"]);
        $this->assertInstanceOf(Dictionary::class, $serialized["data"]);

        $json = json_encode($search);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);
        $this->assertSame($expected["id"], $decoded["id"]);
        $this->assertSame($expected["title"], $decoded["title"]);
        $this->assertSame($expected["data"], $decoded["data"]);
        $this->assertIsArray($decoded["data"]);
    }

    public static function providerJsonSerialize(): array {
        return [
            "with_data" => [
                new Search(5, "TitleX", new Dictionary([ "x" => "xx" ])),
                [
                    "id"    => 5,
                    "title" => "TitleX",
                    "data"  => [ "x" => "xx" ],
                ],
            ],
            "without_data" => [
                new Search("abc", "TitleY"),
                [
                    "id"    => 0,
                    "title" => "TitleY",
                    "data"  => [],
                ],
            ],
        ];
    }


    #[DataProvider("providerCreate")]
    public function testCreate(Dictionary $rows, array|string $nameKey, int $expectedCount, array $expectedChecks): void {
        $list = Search::create($rows, "id", $nameKey);

        $this->assertIsArray($list);
        $this->assertCount($expectedCount, $list);

        foreach ($expectedChecks as $index => $checks) {
            $this->assertSame($checks["id"], $list[$index]->id);
            $this->assertSame($checks["title"], $list[$index]->title);
        }
    }

    public static function providerCreate(): array {
        $rows = new Dictionary([
            [ "id" => "1", "name" => "One", "first" => "First", "last" => "A" ],
            [ "id" => "2", "name" => "Two", "first" => "Second", "last" => "B" ],
            [ "id" => "1", "name" => "Duplicate", "first" => "Dup", "last" => "D" ],
            [ "id" => "x", "name" => "ZeroId", "first" => "Z", "last" => "Z" ],
        ]);

        return [
            "single_name_key" => [
                $rows,
                "name",
                3,
                [
                    0 => [ "id" => 1, "title" => "One" ],
                    1 => [ "id" => 2, "title" => "Two" ],
                    2 => [ "id" => 0, "title" => "ZeroId" ],
                ],
            ],
            "multi_part_name_key" => [
                $rows,
                [ "first", "last" ],
                3,
                [
                    0 => [ "id" => 1, "title" => "First - A" ],
                    1 => [ "id" => 2, "title" => "Second - B" ],
                    2 => [ "id" => 0, "title" => "Z - Z" ],
                ],
            ],
        ];
    }
}
