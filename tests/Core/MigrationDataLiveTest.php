<?php
namespace Tests\Core;

use Framework\Core\MigrationData;

use Tests\LiveTestCase;

/**
 * The Migration Data, the names of the data migrations already applied
 */
class MigrationDataLiveTest extends LiveTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->query("DELETE FROM `migrations`");
    }



    public function testThereIsNoneToStartWith(): void {
        $this->assertTrue(MigrationData::isEmpty());
        $this->assertSame([], MigrationData::getAppliedNames());
    }

    public function testOneAppliedIsRemembered(): void {
        MigrationData::add("the-first-one", "The first one");

        $this->assertFalse(MigrationData::isEmpty());
        $this->assertSame([ "the-first-one" ], MigrationData::getAppliedNames());
    }

    public function testTheNamesComeBackInOrder(): void {
        MigrationData::add("c-one", "The third");
        MigrationData::add("a-one", "The first");
        MigrationData::add("b-one", "The second");

        $this->assertSame([ "a-one", "b-one", "c-one" ], MigrationData::getAppliedNames());
    }

    public function testTheTitleIsKeptBesideTheName(): void {
        MigrationData::add("the-one", "The one that was applied");

        $this->assertSame(
            "The one that was applied",
            MigrationData::getList()[0]->title,
        );
    }
}
