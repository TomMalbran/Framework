<?php
namespace Tests\IO;

use Framework\IO\Errors;

use PHPUnit\Framework\TestCase;

class ErrorsTest extends TestCase {

    public function testConstruct() {
        $e = new Errors([ "a" => "msg" ]);
        $this->assertTrue($e->has("a"));
        $this->assertEquals(1, $e->getTotal());

        // construct should allow null
        $e2 = new Errors(null);
        $this->assertFalse($e2->has());

        // construct with multiple entries
        $e3 = new Errors([ "x" => "1", "y" => "2" ]);
        $this->assertEquals(2, $e3->getTotal());
    }

    public function testMagicSetAndGet() {
        $e = new Errors();
        $e->b = "msg";
        $this->assertTrue($e->has("b"));
        $this->assertEquals("msg", $e->b);

        // get should return empty string for non-existing keys
        $this->assertEquals("", $e->c);

        // set another string via magic and read it
        $e->e = "valid";
        $this->assertEquals("valid", $e->e);

        // set should overwrite existing value
        $e->b = "new";
        $this->assertEquals("new", $e->b);
    }

    public function testIncCount() {
        $e = new Errors();
        $e->incCount("counter", 2);
        $this->assertEquals(2, $e->get()["counter"]);

        // increment again by default amount (1)
        $e->incCount("counter");
        $this->assertEquals(3, $e->get()["counter"]);

        // new counter defaults to 1
        $e->incCount("newCounter");
        $this->assertEquals(1, $e->get()["newCounter"]);
    }

    public function testForm() {
        $e = new Errors();
        $e->form("msg");
        $this->assertEquals("msg", $e->get()["form"]);

        // form should be present in keys and counted
        $this->assertTrue(in_array("form", $e->keys()));

        // form should overwrite existing value
        $e->form("new");
        $this->assertEquals("new", $e->get()["form"]);

        // form should not affect other keys
        $e->add("other", "o");
        $this->assertTrue($e->has("other"));
    }

    public function testGlobal() {
        $e = new Errors();
        $e->global("msg");
        $this->assertEquals("msg", $e->get()["global"]);

        // global should be present and not affect other keys
        $this->assertTrue($e->has("global"));
        $this->assertFalse($e->has("nonexistent"));

        // global should overwrite existing value
        $e->global("new");
        $this->assertEquals("new", $e->get()["global"]);
    }

    public function testAdd() {
        $e = new Errors();
        $e->add("k", "m");
        $this->assertTrue($e->has("k"));
        $this->assertEquals("m", $e->get()["k"]);

        // add should overwrite existing value
        $e->add("k", "new");
        $this->assertEquals("new", $e->get()["k"]);

        // add should allow different keys
        $e->add("k2", "m2");
        $this->assertTrue($e->has("k2"));
        $this->assertEquals("m2", $e->get()["k2"]);

        // adding multiple keys increases total
        $totalBefore = $e->getTotal();
        $e->add("k3", "m3");
        $this->assertEquals($totalBefore + 1, $e->getTotal());

        // add with values should store an array [message, ...values]
        $e->add("kv", "mv", 10, "extra");
        $this->assertTrue($e->has("kv"));
        $this->assertIsArray($e->get()["kv"]);
        $this->assertEquals([ "mv", 10, "extra" ], $e->get()["kv"]);

        // overwriting an existing key with values replaces previous value
        $e->add("k", "third", 5);
        $this->assertIsArray($e->get()["k"]);
        $this->assertEquals([ "third", 5 ], $e->get()["k"]);
    }

    public function testAddIf() {
        $e = new Errors();
        $e->addIf(false, "x", "no");
        $this->assertFalse($e->has("x"));

        $e->addIf(true, "x", "yes");
        $this->assertTrue($e->has("x"));

        // addIf with false again should not change value
        $e->addIf(false, "x", "nope");
        $this->assertEquals("yes", $e->get()["x"]);
    }

    public function testAddFor() {
        $e = new Errors();
        $e->addFor("section", "err", "message");
        $this->assertTrue($e->has("section"));
        $this->assertTrue($e->has("err"));
        $this->assertEquals(2, $e->getTotal());

        // adding another error for same section increases total appropriately
        $e->addFor("section", "err2", "m2");
        $this->assertTrue($e->has("err2"));
        $this->assertEquals(3, $e->getTotal());

        // addFor with values should store an array [message, ...values]
        $e4 = new Errors();
        $e4->addFor("section4", "err4", "m4", 7, "more");
        $this->assertTrue($e4->has("err4"));
        $this->assertIsArray($e4->get()["err4"]);
        $this->assertEquals([ "m4", 7, "more" ], $e4->get()["err4"]);
        $this->assertEquals(1, $e4->get()["section4"]);

        // overwriting the same key with values replaces previous value and increments section count
        $e4->addFor("section4", "err4", "newMsg", 2);
        $this->assertEquals([ "newMsg", 2 ], $e4->get()["err4"]);
        $this->assertEquals(2, $e4->get()["section4"]);
    }

    public function testMerge() {
        $a = new Errors();
        $a->add("one", "o");
        $b = new Errors();
        $b->add("two", "t");
        $a->merge($b);
        $this->assertTrue($a->has("two"));
        $this->assertEquals("t", $a->get()["two"]);

        // merging does not remove existing keys
        $this->assertTrue($a->has("one"));

        // merging with prefix and suffix should modify keys appropriately
        $a = new Errors();
        $a->add("one", "o");
        $b = new Errors();
        $b->add("two", "t");
        $a->merge($b, "p_", "_s");
        $this->assertTrue($a->has("p_two_s"));
        $this->assertEquals("t", $a->get()["p_two_s"]);

        // merging with empty prefix/suffix behaves like normal merge
        $c = new Errors();
        $c->add("three", "3");
        $a->merge($c, "", "");
        $this->assertTrue($a->has("three"));
    }

    public function testMergeFor() {
        $e = new Errors();
        $e2 = new Errors();
        $e2->add("x", "m");
        $e->mergeFor("sec2", $e2);
        $data = $e->get();
        $this->assertArrayHasKey("sec2", $data);
        $this->assertEquals(1, $data["sec2"]);
        $this->assertTrue($e->has("x"));

        // merging another errors object for same section increments count
        $e3 = new Errors();
        $e3->add("y", "n");
        $e->mergeFor("sec2", $e3);
        $this->assertEquals(2, $e->get()["sec2"]);
        $this->assertTrue($e->has("y"));

        // merging with prefix/suffix should rename keys and count correctly
        $src = new Errors();
        $src->add("arr", "val", 1);
        $e->mergeFor("sec3", $src, "p_", "_s");
        $this->assertTrue($e->has("p_arr_s"));
        $this->assertEquals(["val", 1], $e->get()["p_arr_s"]);
        $this->assertEquals(1, $e->get()["sec3"]);

        // merging an empty Errors object should not change the section count
        $before = $e->get()["sec3"];
        $empty = new Errors();
        $e->mergeFor("sec3", $empty);
        $this->assertEquals($before, $e->get()["sec3"]);

        // merging where keys collide should overwrite the key value and still increment
        $collideSrc = new Errors();
        $collideSrc->add("x", "overwritten");
        $prev = $e->get()["sec2"];
        $e->mergeFor("sec2", $collideSrc);
        $this->assertEquals($prev + 1, $e->get()["sec2"]);
        $this->assertEquals("overwritten", $e->get()["x"]);
    }

    public function testHas() {
        $e = new Errors();
        $e->add("field-name-error", "err");
        $this->assertTrue($e->has("field-name"));

        // original full key should also be present
        $this->assertTrue($e->has("field-name-error"));
        $this->assertFalse($e->has("field"));

        // has() with no arguments should return true when there is at least one error
        $this->assertTrue($e->has());

        // a fresh Errors object has no errors
        $empty = new Errors();
        $this->assertFalse($empty->has());

        // has() accepts an array and returns true if any of the keys exist
        $this->assertTrue($e->has([ "nope", "field-name" ]));
    }

    public function testKeys() {
        $e = new Errors();
        $e->add("a", "v");
        $e->add("b", "w");
        $keys = $e->keys();
        $this->assertContains("a", $keys);
        $this->assertContains("b", $keys);
        $this->assertCount(2, $keys);

        // keys after merge with prefix/suffix are reflected
        $src = new Errors();
        $src->add("c", "cv", 1);
        $e->merge($src, "p_", "_s");
        $this->assertTrue(in_array("p_c_s", $e->keys()));

        // form and global appear in keys
        $e->form("fromMsg");
        $e->global("globalMsg");
        $this->assertTrue(in_array("form", $e->keys()));
        $this->assertTrue(in_array("global", $e->keys()));
    }

    public function testGetTotal() {
        $e = new Errors();
        $this->assertEquals(0, $e->getTotal());

        $e->add("a", "v");
        $this->assertEquals(1, $e->getTotal());

        $e->addFor("sec", "err", "m");
        // addFor adds both the section counter and the error key
        $this->assertEquals(3, $e->getTotal());

        // merging another Errors increases total by that object's total
        $other = new Errors();
        $other->add("x", "xv");
        $other->addFor("s2", "e2", "m2");
        $before = $e->getTotal();
        $e->merge($other);
        $this->assertEquals($before + $other->getTotal(), $e->getTotal());
    }

    public function testJsonSerialize() {
        $e = new Errors();
        $e->add("a", "v");
        $e->add("b", "w");
        $e->add("arr", "val", 1);
        $e->form("fromMsg");
        $e->global("globalMsg");

        $json = json_encode($e);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey("a", $decoded);
        $this->assertArrayHasKey("b", $decoded);
        $this->assertArrayHasKey("arr", $decoded);
        $this->assertArrayHasKey("form", $decoded);
        $this->assertArrayHasKey("global", $decoded);
        $this->assertEquals($e->get(), $decoded);

        // empty Errors json encodes to empty array
        $e2 = new Errors();
        $this->assertEquals([], $e2->jsonSerialize());
        $this->assertEquals("[]", json_encode($e2));
    }
}
