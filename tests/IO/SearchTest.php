<?php
namespace Tests\IO;

use Framework\IO\Search;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase {

    public function testConstruct() {
        // integer id
        $d = new Dictionary([ "k" => "v" ]);
        $s = new Search(10, "Title", $d);
        $this->assertEquals(10, $s->id);
        $this->assertEquals("Title", $s->title);
        $this->assertSame($d, $s->data);

        // string numeric id should coerce to int
        $s2 = new Search("20", "T2", null);
        $this->assertIsInt($s2->id);
        $this->assertEquals(20, $s2->id);

        // non-numeric id coerces to 0 when cast to int
        $s3 = new Search("nope", "T3");
        $this->assertEquals(0, $s3->id);
    }

    public function testGetString() {
        $data = new Dictionary([ "field" => "value", "num" => 5 ]);
        $s = new Search(1, "MyTitle", $data);

        // property access returns title/id as string
        $this->assertEquals("MyTitle", $s->getString("title"));
        $this->assertEquals("1", $s->getString("id"));

        // data-backed keys
        $this->assertEquals("value", $s->getString("field"));
        $this->assertEquals("5", $s->getString("num"));

        // missing key returns empty string
        $this->assertEquals("", $s->getString("missing"));
    }

    public function testGetInt() {
        $data = new Dictionary([ "a" => "3", "b" => 7, "bad" => [ 1, 2 ]]);
        $s = new Search("2", "T", $data);

        // properties: id and title
        $this->assertEquals(2, $s->getInt("id"));
        $this->assertEquals(0, $s->getInt("title"));

        // data backed
        $this->assertEquals(3, $s->getInt("a"));
        $this->assertEquals(7, $s->getInt("b"));

        // arrays should return 0
        $this->assertEquals(0, $s->getInt("bad"));

        // missing key returns 0
        $this->assertEquals(0, $s->getInt("missing"));
    }

    public function testJsonSerialize() {
        $data = new Dictionary(["x" => "xx"]);
        $s = new Search(5, "TitleX", $data);

        $serialized = $s->jsonSerialize();
        $this->assertIsArray($serialized);
        $this->assertArrayHasKey("id", $serialized);
        $this->assertArrayHasKey("title", $serialized);
        $this->assertArrayHasKey("data", $serialized);
        $this->assertEquals(5, $serialized["id"]);
        $this->assertEquals("TitleX", $serialized["title"]);
        $this->assertInstanceOf(Dictionary::class, $serialized["data"]);

        // json encode roundtrip
        $json = json_encode($s);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($serialized["id"], $decoded["id"]);
        $this->assertEquals($serialized["title"], $decoded["title"]);
        $this->assertIsArray($decoded["data"]);
    }

    public function testCreate() {
        // prepare rows: list of arrays with id and name
        $rows = new Dictionary([
            [ "id" => "1", "name" => "One", "first" => "First", "last" => "A" ],
            [ "id" => "2", "name" => "Two", "first" => "Second", "last" => "B" ],
            [ "id" => "1", "name" => "Duplicate", "first" => "Dup", "last" => "D" ],
            [ "id" => "x", "name" => "ZeroId", "first" => "Z", "last" => "Z" ],
        ]);

        // single name key
        $list = Search::create($rows, "id", "name");
        $this->assertIsArray($list);
        $this->assertCount(3, $list); // duplicate id filtered
        $this->assertEquals(1, $list[0]->id);
        $this->assertEquals("One", $list[0]->title);

        // multi-part name key
        $list2 = Search::create($rows, "id", ["first", "last"]);
        $this->assertEquals("First - A", $list2[0]->title);

        // non-numeric id becomes 0 when cast in constructor
        $last = end($list2);
        $this->assertEquals(0, $last->id);
    }
}
