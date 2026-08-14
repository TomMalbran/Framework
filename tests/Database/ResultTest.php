<?php
namespace Tests\Database;

use Framework\Database\Type\Result;
use Framework\IO\Errors;

use PHPUnit\Framework\TestCase;

/**
 * The Result, which is what the validation of a generated Schema hands back
 *
 * A Model of the Framework has nothing to validate, so nothing builds one of
 * these here: it is the Schema of an App that does, the same way the code
 * written for the Models of the tests does.
 */
class ResultTest extends TestCase {

    public function testAResultStartsWithNoError(): void {
        $result = new Result(true, new Errors());

        $this->assertTrue($result->canValidate);
        $this->assertFalse($result->hasError());
    }

    public function testAnErrorIsAdded(): void {
        $result = new Result(true, new Errors());

        $this->assertSame($result, $result->addError("name", "GENERAL_ERROR_NAME"));

        $this->assertTrue($result->hasError());
        $this->assertSame("GENERAL_ERROR_NAME", $result->errors->name);
    }

    public function testOneThatCannotValidateSaysSo(): void {
        $result = new Result(false, new Errors());

        $this->assertFalse($result->canValidate);
        $this->assertFalse($result->hasError());
    }
}
