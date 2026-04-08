<?php
namespace Tests\IO;

use Framework\IO\Response;
use Framework\IO\Search;
use Framework\IO\Errors;
use Framework\Utils\JSON;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {

    public function testAddTokens() {
        // with tokens enabled
        $r1 = Response::empty(true);
        $r1->addTokens("a-token", "r-token");
        $a1 = $r1->toArray();
        $this->assertArrayHasKey("xAccessToken", $a1);
        $this->assertArrayHasKey("xRefreshToken", $a1);
        $this->assertSame("a-token", $a1["xAccessToken"]);
        $this->assertSame("r-token", $a1["xRefreshToken"]);

        // empty refresh token should omit refresh field
        $r2 = Response::empty(true);
        $r2->addTokens("only-access", "");
        $a2 = $r2->toArray();
        $this->assertArrayHasKey("xAccessToken", $a2);
        $this->assertArrayNotHasKey("xRefreshToken", $a2);

        // withTokens disabled should not add tokens
        $r3 = Response::empty(false);
        $r3->addTokens("x","y");
        $a3 = $r3->toArray();
        $this->assertArrayNotHasKey("xAccessToken", $a3);
    }

    public function testToArray() {
        $data = [ "a" => 1, "b" => "v" ];
        $r = new Response($data, true);
        $this->assertSame($data, $r->toArray());
    }

    public function testPrintData() {
        $payload = [ "k" => "v", "n" => 3 ];
        $r1 = Response::data($payload);
        $expected = JSON::encode($payload, asPretty: true);
        ob_start();
        $r1->printData();
        $o1 = ob_get_clean();
        $this->assertSame($expected, $o1);

        // should print empty JSON object if data is empty
        $r2 = Response::data([]);
        ob_start();
        $r2->printData();
        $o2 = ob_get_clean();
        $this->assertSame("[]", $o2);

        // should print nothing if "data" key is missing
        $r3 = Response::result([ "x" => 1 ]);
        ob_start();
        $r3->printData();
        $o3 = ob_get_clean();
        $this->assertSame("", $o3);
    }

    public function testEmpty() {
        // default empty response
        $e = Response::empty();
        $this->assertSame([], $e->toArray());

        // with tokens enabled the empty response should accept tokens
        $e2 = Response::empty(true);
        $e2->addTokens("a", "b");
        $a = $e2->toArray();
        $this->assertArrayHasKey("xAccessToken", $a);
    }

    public function testExit() {
        // exit: result should be present and tokens disabled
        $r1 = Response::exit(7);
        $a1 = $r1->toArray();
        $this->assertArrayHasKey("result", $a1);
        $this->assertSame(7, $a1["result"]);
        $r1->addTokens("a","b");
        $this->assertArrayNotHasKey("xAccessToken", $r1->toArray());

        // exit with different code
        $r2 = Response::exit(0);
        $a2 = $r2->toArray();
        $this->assertArrayHasKey("result", $a2);
        $this->assertSame(0, $a2["result"]);
    }

    public function testResult() {
        // result helper wraps provided array as top-level data
        $r1 = Response::result([ "res" => 1 ]);
        $this->assertSame([ "res" => 1 ], $r1->toArray());

        // empty result preserved
        $r2 = Response::result([]);
        $this->assertSame([], $r2->toArray());
    }

    public function testData() {
        // data creates a "data" key with provided payload
        $r1 = Response::data([ "x" => 2 ]);
        $this->assertArrayHasKey("data", $r1->toArray());
        $this->assertSame([ "x" => 2 ], $r1->toArray()["data"]);

        // data with empty payload should still create "data" key
        $r2 = Response::data([]);
        $a2 = $r2->toArray();
        $this->assertArrayHasKey("data", $a2);

        // data should allow a JSON-serializable object as payload
        $s = new Search(1, "T", null);
        $r3 = Response::data($s);
        $a3 = $r3->toArray();
        $this->assertArrayHasKey("data", $a3);
        $this->assertInstanceOf(Search::class, $a3["data"]);
    }

    public function testSearch() {
        $s1 = new Search(1, "T", null);
        $r1 = Response::search([ $s1 ]);
        $this->assertArrayHasKey("data", $r1->toArray());
        $this->assertInstanceOf(Search::class, $r1->toArray()["data"][0]);

        // empty search list should produce empty data array
        $r2 = Response::search([]);
        $this->assertArrayHasKey("data", $r2->toArray());
        $this->assertSame([], $r2->toArray()["data"]);
    }

    public function testInvalid() {
        $r = Response::invalid();
        $this->assertArrayHasKey("data", $r->toArray());
        $this->assertSame([ "error" => true ], $r->toArray()["data"]);
    }

    public function testLogout() {
        $r = Response::logout();
        $this->assertArrayHasKey("userLoggedOut", $r->toArray());
        $this->assertTrue($r->toArray()["userLoggedOut"]);
    }

    public function testSuccess() {
        // success with null data and default param
        $r1 = Response::success("ok");
        $a1 = $r1->toArray();
        $this->assertArrayHasKey("success", $a1);
        $this->assertSame("ok", $a1["success"]);
        $this->assertArrayHasKey("param", $a1);
        $this->assertSame("", $a1["param"]);
        $this->assertArrayHasKey("data", $a1);
        $this->assertNull($a1["data"]);

        // success with data and param
        $r2 = Response::success("done", [ "x" => 1 ], "p");
        $a2 = $r2->toArray();
        $this->assertSame("p", $a2["param"]);
        $this->assertSame([ "x" => 1 ], $a2["data"]);
    }

    public function testWarning() {
        // warning with default param and null data
        $r1 = Response::warning("be careful");
        $a1 = $r1->toArray();
        $this->assertArrayHasKey("warning", $a1);
        $this->assertSame("be careful", $a1["warning"]);
        $this->assertArrayHasKey("param", $a1);
        $this->assertSame("", $a1["param"]);
        $this->assertArrayHasKey("data", $a1);
        $this->assertNull($a1["data"]);

        // warning mirrors same shape
        $r2 = Response::warning("warn", null, "pp");
        $a2 = $r2->toArray();
        $this->assertArrayHasKey("warning", $a2);
        $this->assertSame("warn", $a2["warning"]);
        $this->assertSame("pp", $a2["param"]);
    }

    public function testError() {
        // string error
        $r1 = Response::error("oops", null, "p1");
        $a1 = $r1->toArray();
        $this->assertArrayHasKey("error", $a1);
        $this->assertSame("oops", $a1["error"]);
        $this->assertArrayHasKey("params", $a1);
        $this->assertSame([ "p1" ], $a1["params"]);

        // with multiple params
        $r2 = Response::error("error", null, [ "pA", "pB" ]);
        $a2 = $r2->toArray();
        $this->assertArrayHasKey("error", $a2);
        $this->assertSame("error", $a2["error"]);
        $this->assertArrayHasKey("params", $a2);
        $this->assertSame([ "pA", "pB" ], $a2["params"]);

        // Errors instance with global
        $e3 = new Errors();
        $e3->global("global-msg");
        $r3 = Response::error($e3, [ "dd" => 1 ], [ "pA", "pB" ]);
        $a3 = $r3->toArray();
        $this->assertArrayHasKey("error", $a3);
        $this->assertSame("global-msg", $a3["error"]);
        $this->assertSame("pA", $a3["param"]);
        $this->assertSame([ "pA", "pB" ], $a3["params"]);
        $this->assertSame([ "dd" => 1 ], $a3["data"]);

        // Errors instance without global should produce "errors" key
        $e4 = new Errors();
        $e4->add("f", "m");
        $r4 = Response::error($e4);
        $a4 = $r4->toArray();
        $this->assertArrayHasKey("errors", $a4);
        $this->assertIsArray($a4["errors"]);
    }
}
