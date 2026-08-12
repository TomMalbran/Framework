<?php
namespace Tests\Auth;

use Framework\Auth\Spam;

use Tests\LiveTestCase;

/**
 * The Spam protection, which refuses a second submit from one address
 *
 * It keeps a row per address and drops the ones older than a second, so two
 * calls in a row are the whole of it: the first is let through and leaves the
 * row, and the second finds it.
 */
class SpamLiveTest extends LiveTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        Spam::reset();
    }



    public function testTheFirstSubmitIsLetThrough(): void {
        $this->assertFalse(Spam::protect());
    }

    public function testTheSecondSubmitIsRefused(): void {
        Spam::protect();

        $this->assertTrue(Spam::protect());
    }

    public function testResettingLetsTheNextOneThrough(): void {
        Spam::protect();
        $this->assertTrue(Spam::protect());

        Spam::reset();
        $this->assertFalse(Spam::protect());
    }

    public function testResettingWithNothingToResetSaysSo(): void {
        // There is no row for this address, so nothing was removed
        $this->assertFalse(Spam::reset());
    }
}
