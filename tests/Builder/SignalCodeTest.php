<?php
namespace Tests\Builder;

use Framework\Builder\SignalCode;
use Framework\Discovery\Attr\Listener;
use Framework\Discovery\Type\DiscoveryClass;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TestSignalEmpty {
}

class TestSignalNoListeners {
    public static function helper(int $value): void {
    }
}

class TestSignalBasic {
    #[Listener("create")]
    public static function onCreate(int $credentialID): void {
    }

    #[Listener("delete")]
    public static function onDelete(int $credentialID): void {
    }
}

class TestSignalShared {
    #[Listener("create")]
    public static function alsoOnCreate(int $credentialID, string $name): void {
    }
}

class TestSignalTypes {
    #[Listener("types")]
    public static function onTypes(
        ?Dictionary $data,
        int|string $mixedValue,
        array $userIDs,
        $untyped,
    ): void {
    }
}

class TestSignalMulti {
    #[Listener("alpha", "beta")]
    public static function onBoth(int $credentialID): void {
    }
}


class SignalCodeTest extends TestCase {

    /**
     * Builds the DiscoveryClasses for the given class names
     * @param list<string> $classNames
     * @return list<DiscoveryClass>
     */
    private static function toClasses(array $classNames): array {
        $result = [];
        foreach ($classNames as $className) {
            $result[] = new DiscoveryClass($className);
        }
        return $result;
    }


    #[DataProvider("providerCollectSignalsEmpty")]
    public function testCollectSignalsEmpty(array $classNames): void {
        $this->assertSame([], SignalCode::collectSignals(self::toClasses($classNames)));
    }

    public static function providerCollectSignalsEmpty(): array {
        return [
            "no_classes"   => [ [] ],
            "empty_class"  => [ [ TestSignalEmpty::class ] ],
            "no_listeners" => [ [ TestSignalNoListeners::class ] ],
        ];
    }


    public function testCollectSignalsSortsByEvent(): void {
        $result = SignalCode::collectSignals(self::toClasses([ TestSignalBasic::class ]));

        $events = array_map(fn($signal) => $signal["event"], $result["signals"]);
        $this->assertSame([ "create", "delete" ], $events);
        $this->assertSame(2, $result["total"]);
    }


    public function testCollectSignalsMergesParamsAcrossListeners(): void {
        $result = SignalCode::collectSignals(self::toClasses([
            TestSignalBasic::class,
            TestSignalShared::class,
        ]));

        $create = $result["signals"][0];
        $this->assertSame("create", $create["event"]);

        // The shared "create" event merges the params of both listeners, without duplicates
        $names = array_map(fn($param) => $param["name"], $create["params"]);
        $this->assertSame([ "credentialID", "name" ], $names);

        // Only the first param is flagged as first
        $this->assertTrue($create["params"][0]["isFirst"]);
        $this->assertFalse($create["params"][1]["isFirst"]);

        // Both methods are registered as triggers
        $triggers = array_map(fn($trigger) => $trigger["name"], $create["triggers"]);
        $this->assertSame([
            "\\" . TestSignalBasic::class . "::onCreate",
            "\\" . TestSignalShared::class . "::alsoOnCreate",
        ], $triggers);
    }


    public function testCollectSignalsRegistersEachTrigger(): void {
        $result = SignalCode::collectSignals(self::toClasses([ TestSignalMulti::class ]));

        $events = array_map(fn($signal) => $signal["event"], $result["signals"]);
        $this->assertSame([ "alpha", "beta" ], $events);
        $this->assertSame(2, $result["total"]);
    }


    #[DataProvider("providerCollectSignalsParamTypes")]
    public function testCollectSignalsParamTypes(int $index, string $type, string $docType): void {
        $result = SignalCode::collectSignals(self::toClasses([ TestSignalTypes::class ]));
        $param  = $result["signals"][0]["params"][$index];

        $this->assertSame($type, $param["type"]);
        $this->assertSame($docType, $param["docType"]);
    }

    public static function providerCollectSignalsParamTypes(): array {
        return [
            // A nullable class type is shortened and gets a "null" alternative
            "nullable_class" => [ 0, "Dictionary|null", "Dictionary|null" ],
            // PHP normalizes the declared union order
            "union_builtin"  => [ 1, "string|int", "string|int" ],
            // Params ending in "IDs" are documented as a list of ints
            "ids_param"      => [ 2, "array", "list<int>" ],
            "untyped"        => [ 3, "mixed|null", "mixed|null" ],
        ];
    }


    public function testCollectSignalsCollectsUses(): void {
        $result = SignalCode::collectSignals(self::toClasses([ TestSignalTypes::class ]));

        $this->assertTrue($result["hasUses"]);
        $this->assertContains(Dictionary::class, $result["uses"]);
    }


    public function testCollectSignalsWithoutUses(): void {
        $result = SignalCode::collectSignals(self::toClasses([ TestSignalBasic::class ]));

        $this->assertFalse($result["hasUses"]);
        $this->assertSame([], $result["uses"]);
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, SignalCode::destroyCode());
    }
}
