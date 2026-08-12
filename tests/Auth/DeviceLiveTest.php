<?php
namespace Tests\Auth;

use Framework\Auth\Device;

use Tests\LiveTestCase;

/**
 * The Devices a Credential is reachable on, which a push is sent to
 */
class DeviceLiveTest extends LiveTestCase {

    private const CredentialID = 900001;
    private const OtherID      = 900002;
    private const PlayerID     = "a-player-of-the-tests";
    private const OtherPlayer  = "another-player";


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        foreach ([ self::CredentialID, self::OtherID ] as $credentialID) {
            foreach ([ self::PlayerID, self::OtherPlayer ] as $playerID) {
                Device::remove($credentialID, $playerID);
            }
        }
    }



    public function testACredentialWithNoDeviceHasNone(): void {
        $this->assertFalse(Device::has(self::CredentialID));
        $this->assertSame([], Device::getAllForCredential(self::CredentialID));
    }

    public function testTheDeviceIsAdded(): void {
        $this->assertTrue(Device::add(self::CredentialID, self::PlayerID));

        $this->assertTrue(Device::has(self::CredentialID));
        $this->assertSame([ self::PlayerID ], Device::getAllForCredential(self::CredentialID));
    }

    public function testACredentialHoldsMoreThanOneDevice(): void {
        Device::add(self::CredentialID, self::PlayerID);
        Device::add(self::CredentialID, self::OtherPlayer);

        $result = Device::getAllForCredential(self::CredentialID);
        sort($result);

        $this->assertSame([ self::PlayerID, self::OtherPlayer ], $result);
    }

    public function testAddingTheSameDeviceTwiceLeavesOne(): void {
        // The row is replaced rather than added again, so the pair is unique
        Device::add(self::CredentialID, self::PlayerID);
        Device::add(self::CredentialID, self::PlayerID);

        $this->assertSame([ self::PlayerID ], Device::getAllForCredential(self::CredentialID));
    }

    public function testTheDeviceIsRemoved(): void {
        Device::add(self::CredentialID, self::PlayerID);

        $this->assertTrue(Device::remove(self::CredentialID, self::PlayerID));
        $this->assertFalse(Device::has(self::CredentialID));
    }

    public function testRemovingLeavesTheOtherDevices(): void {
        Device::add(self::CredentialID, self::PlayerID);
        Device::add(self::CredentialID, self::OtherPlayer);

        Device::remove(self::CredentialID, self::PlayerID);

        $this->assertSame([ self::OtherPlayer ], Device::getAllForCredential(self::CredentialID));
    }

    public function testTheDevicesOfTwoCredentialsAreKeptApart(): void {
        Device::add(self::CredentialID, self::PlayerID);
        Device::add(self::OtherID, self::OtherPlayer);

        $this->assertSame([ self::PlayerID ], Device::getAllForCredential(self::CredentialID));
        $this->assertSame([ self::OtherPlayer ], Device::getAllForCredential(self::OtherID));
    }

    public function testTheDevicesOfSeveralCredentialsComeBackTogether(): void {
        Device::add(self::CredentialID, self::PlayerID);
        Device::add(self::OtherID, self::OtherPlayer);

        $result = Device::getAllForCredential([ self::CredentialID, self::OtherID ]);
        sort($result);

        $this->assertSame([ self::PlayerID, self::OtherPlayer ], $result);
    }

    public function testAskingForNoCredentialFindsNothing(): void {
        Device::add(self::CredentialID, self::PlayerID);

        $this->assertSame([], Device::getAllForCredential([]));
        $this->assertSame([], Device::getAllForCredential(0));
    }
}
