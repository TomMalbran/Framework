<?php
namespace Tests\Log;

use Framework\Discovery\Type\DiscoveryClass;
use Framework\Log\ActionLogBuilder;
use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TestLogNoSection {
    #[Action("shipped")]
    public static function shipped(): void {
    }
}

#[Section("orders")]
class TestLogNoActions {
}

#[Section("")]
class TestLogNoName {
    #[Action("shipped")]
    public static function shipped(): void {
    }
}

#[Section("products", es: "Productos", en: "Products")]
class TestLogProducts {
    #[Action("created", es: "Creado", en: "Created")]
    public static function created(): void {
    }

    #[Action("")]
    public static function nameless(): void {
    }
}

#[Section("orders", es: "Pedidos")]
class TestLogOrders {
    #[Action("shipped", es: "Enviado")]
    public static function shipped(): void {
    }

    #[Action("cancelled")]
    #[Action("refunded")]
    public static function twoAtOnce(): void {
    }
}

#[Section("returns")]
class TestLogInherited extends TestLogOrders {
}


/**
 * The Action Log Builder, which reads the Sections and Actions of an App
 *
 * The generate and the destroy are left alone: one writes the Sec, Act and
 * LogIntl of this repository from templates the build loads, so called from
 * here it would write them empty, and the other deletes them outright. What is
 * covered is the collecting they are handed.
 */
class ActionLogBuilderTest extends TestCase {

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

    /**
     * Collects the given classes
     * @param list<string> $classNames
     * @return array<string,mixed>
     */
    private static function collect(array $classNames): array {
        return ActionLogBuilder::collectSections(self::toClasses($classNames));
    }



    /**
     * Classes the builder finds nothing in, which fall back to an Example
     * @param list<string> $classNames
     * @return void
     */
    #[DataProvider("providerNothing")]
    public function testThereIsNothingToCollect(array $classNames): void {
        $result = self::collect($classNames);

        $this->assertSame([ "Example" ], $result["sections"]);
        $this->assertSame([ "Example" ], $result["actions"]);
        $this->assertSame([], $result["modules"]);
        $this->assertSame([], $result["languages"]);
        $this->assertSame(0, $result["total"]);
    }

    /**
     * @return array<string,array{list<string>}>
     */
    public static function providerNothing(): array {
        return [
            "no classes"     => [ [] ],
            "no section"     => [ [ TestLogNoSection::class ] ],
            "a nameless one" => [ [ TestLogNoName::class ] ],
        ];
    }

    public function testASectionIsCollected(): void {
        $result = self::collect([ TestLogOrders::class ]);

        $this->assertSame([ "Orders" ], $result["sections"]);
        $this->assertSame(1, $result["total"]);
        $this->assertSame([
            [ "name" => "Orders", "label" => "\"orders\"" ],
        ], $result["modules"]);
    }

    public function testTheNamesAreMadePascalCase(): void {
        // They are written as they read, and the enum needs a case name
        $result = self::collect([ TestLogOrders::class ]);

        $this->assertSame([ "Orders" ], $result["sections"]);
        $this->assertSame([ "Cancelled", "Refunded", "Shipped" ], $result["actions"]);
    }

    public function testASectionWithNoActionsIsKept(): void {
        $result = self::collect([ TestLogNoActions::class ]);

        $this->assertSame([ "Orders" ], $result["sections"]);
        $this->assertSame([ "Example" ], $result["actions"]);
    }

    public function testAMethodTakesSeveralActions(): void {
        $result = self::collect([ TestLogOrders::class ]);

        $this->assertContains("Cancelled", $result["actions"]);
        $this->assertContains("Refunded", $result["actions"]);
    }

    public function testANamelessActionIsSkipped(): void {
        $result = self::collect([ TestLogProducts::class ]);

        $this->assertSame([ "Created" ], $result["actions"]);
    }

    public function testTheParentActionsAreNotTaken(): void {
        // A class is asked for the methods it declares itself, so a Section
        // that extends another does not take the actions of it as its own
        $result = self::collect([ TestLogInherited::class ]);

        $this->assertSame([ "Returns" ], $result["sections"]);
        $this->assertSame([ "Example" ], $result["actions"]);
    }

    public function testTheSectionsAndActionsAreSorted(): void {
        $result = self::collect([ TestLogProducts::class, TestLogOrders::class ]);

        $this->assertSame([ "Orders", "Products" ], $result["sections"]);
        $this->assertSame([ "Cancelled", "Created", "Refunded", "Shipped" ], $result["actions"]);
    }

    public function testTheSameSectionTwiceIsOne(): void {
        $result = self::collect([ TestLogOrders::class, TestLogOrders::class ]);

        $this->assertSame([ "Orders" ], $result["sections"]);
        $this->assertSame(1, $result["total"]);
    }



    public function testALanguageIsFoundInTheTranslations(): void {
        $result = self::collect([ TestLogOrders::class ]);

        $this->assertCount(1, $result["languages"]);
        $this->assertSame("\"es\"", $result["languages"][0]["language"]);
        $this->assertSame("Es", $result["languages"][0]["method"]);
    }

    public function testTheLanguagesAreSortedAndUnique(): void {
        // Both the Sections and the Actions name them, and each is one entry
        $result = self::collect([ TestLogProducts::class, TestLogOrders::class ]);

        $methods = array_column($result["languages"], "method");
        $this->assertSame([ "En", "Es" ], $methods);
    }

    public function testEveryLanguageGetsEverySection(): void {
        $result = self::collect([ TestLogProducts::class, TestLogOrders::class ]);

        foreach ($result["languages"] as $language) {
            $this->assertCount(2, $language["sections"]);
        }
    }

    public function testASectionCarriesItsTranslation(): void {
        $result = self::collect([ TestLogOrders::class ]);

        $section = $result["languages"][0]["sections"][0];
        $this->assertSame("Orders", $section["name"]);
        $this->assertSame("\"Pedidos\"", $section["label"]);
        $this->assertContains(
            [ "name" => "Shipped", "label" => "\"Enviado\"" ],
            $section["actions"],
        );
    }

    public function testWhatIsNotTranslatedIsEmpty(): void {
        // Orders is only in Spanish, so the English code says nothing for it
        // rather than falling back to the name it was declared with
        $result = self::collect([ TestLogProducts::class, TestLogOrders::class ]);

        $english = $result["languages"][0];
        $this->assertSame("En", $english["method"]);
        $this->assertSame("\"\"", $english["sections"][0]["label"]);
        $this->assertSame("\"Products\"", $english["sections"][1]["label"]);
    }

    public function testAnActionWithNoTranslationIsEmpty(): void {
        $result = self::collect([ TestLogOrders::class ]);

        $actions = $result["languages"][0]["sections"][0]["actions"];
        $labels  = array_column($actions, "label");
        $this->assertContains("\"\"", $labels);
    }

    public function testThereAreNoLanguagesWithoutTranslations(): void {
        $result = self::collect([ TestLogNoActions::class ]);

        $this->assertSame([], $result["languages"]);
        $this->assertSame([ "Orders" ], $result["sections"]);
    }
}
