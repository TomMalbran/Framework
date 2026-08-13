<?php
namespace Tests\Log;

use Framework\Log\DeviceLog;
use Framework\Log\Schema\LogDeviceRequest;

use Tests\LiveTestCase;

/**
 * The Devices Log, a row for each device that came and went
 */
class DeviceLogLiveTest extends LiveTestCase {

    private const CredentialID = 9101;
    private const PlayerID     = "a-player-id";


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->query("DELETE FROM `log_device`");
    }



    public function testADeviceAddedIsLogged(): void {
        $logID = DeviceLog::added(self::CredentialID, self::PlayerID);

        $this->assertGreaterThan(0, $logID);

        $log = DeviceLog::getByID($logID);
        $this->assertSame(self::CredentialID, $log->credentialID);
        $this->assertSame(self::PlayerID, $log->playerID);
        $this->assertTrue($log->wasAdded);
    }

    public function testADeviceRemovedIsLogged(): void {
        $logID = DeviceLog::removed(self::CredentialID, self::PlayerID);

        $log = DeviceLog::getByID($logID);
        $this->assertSame(self::PlayerID, $log->playerID);
        $this->assertFalse($log->wasAdded);
    }

    public function testBothEndsOfADeviceAreKept(): void {
        // The log is what happened rather than what is, so the two rows of a
        // device that came and went both stay
        DeviceLog::added(self::CredentialID, self::PlayerID);
        DeviceLog::removed(self::CredentialID, self::PlayerID);

        $this->assertSame(2, DeviceLog::getEntityTotal());
    }

    public function testTheListIsSearched(): void {
        DeviceLog::added(self::CredentialID, self::PlayerID);
        DeviceLog::added(self::CredentialID, "another-player-id");

        $request = new LogDeviceRequest(search: "another");
        $this->assertSame(1, DeviceLog::getTotal($request));
        $this->assertSame("another-player-id", DeviceLog::getList($request)[0]->playerID);
    }

    public function testTheOldOnesAreDeleted(): void {
        $logID = DeviceLog::added(self::CredentialID, self::PlayerID);
        $this->query(
            "UPDATE `log_device` SET `createdTime` = UNIX_TIMESTAMP() - 91 * 86400 " .
            "WHERE `LOG_ID` = $logID",
        );
        $young = DeviceLog::added(self::CredentialID, "another-player-id");

        $this->assertTrue(DeviceLog::deleteOld());

        $this->assertFalse(DeviceLog::exists($logID));
        $this->assertTrue(DeviceLog::exists($young));
    }

    public function testThereIsNothingOldToDelete(): void {
        DeviceLog::added(self::CredentialID, self::PlayerID);

        $this->assertFalse(DeviceLog::deleteOld());
    }
}
