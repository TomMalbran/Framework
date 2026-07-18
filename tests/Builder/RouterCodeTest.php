<?php
namespace Tests\Builder;

use Framework\Builder\RouterCode;
use Framework\Discovery\Attr\Route;
use Framework\Discovery\Type\DiscoveryClass;
use Framework\IO\Request;
use Framework\System\Access;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TestRouterRequest {
    public string $value = "";
}

class TestRouterEmpty {
}

class TestRouterOther {
    public static function helper(): void {
    }
}

class TestRouterRoutes {
    #[Route("/basic", Access::General)]
    public static function basic(): void {
    }

    #[Route("/request", Access::Admin)]
    public static function withRequest(Request $request): void {
    }

    #[Route("/custom", Access::API)]
    public static function withCustom(TestRouterRequest $request): void {
    }
}

class TestRouterSkipped {
    #[Route("/tooManyParams", Access::General)]
    public static function tooManyParams(Request $request, int $extra): void {
    }

    #[Route("/untypedParam", Access::General)]
    public static function untypedParam($param): void {
    }

    #[Route("/otherParam", Access::General)]
    public static function otherParam(int $value): void {
    }
}


class RouterCodeTest extends TestCase {
    use TestHelpers;

    /**
     * Builds the DiscoveryClasses for the given class names
     */
    private static function toClasses(array $classNames): array {
        $result = [];
        foreach ($classNames as $className) {
            $result[] = new DiscoveryClass($className);
        }
        return $result;
    }


    #[DataProvider("providerCollectRoutes")]
    public function testCollectRoutes(array $classNames, array $expected): void {
        $result = RouterCode::collectRoutes(self::toClasses($classNames));

        $this->assertSame($expected, $result["routes"]);
        $this->assertSame(count($expected), $result["total"]);
        $this->assertSame(count($expected) > 0, $result["hasRoutes"]);
    }

    public static function providerCollectRoutes(): array {
        $basicPad  = 10;
        $singlePad = 13;

        return [
            "no_classes"                 => [
                [],
                [],
            ],
            "class_without_methods"      => [
                [ TestRouterEmpty::class ],
                [],
            ],
            "class_without_routes"       => [
                [ TestRouterOther::class ],
                [],
            ],
            "collects_and_aligns_routes" => [
                [ TestRouterRoutes::class ],
                [
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "basic",
                        "requestParam" => "",
                        "route"        => str_pad("\"/basic\"", $basicPad),
                        "access"       => "General",
                        "addSpace"     => false,
                    ],
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "withRequest",
                        "requestParam" => "\$request",
                        "route"        => str_pad("\"/request\"", $basicPad),
                        "access"       => "Admin",
                        "addSpace"     => false,
                    ],
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "withCustom",
                        "requestParam" => "\\Tests\\Builder\\TestRouterRequest::fromRequest(\$request)",
                        "route"        => str_pad("\"/custom\"", $basicPad),
                        "access"       => "API",
                        "addSpace"     => false,
                    ],
                ],
            ],
            "skips_invalid_params"       => [
                [ TestRouterSkipped::class ],
                [
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterSkipped",
                        "method"       => "otherParam",
                        "requestParam" => "",
                        "route"        => str_pad("\"/otherParam\"", $singlePad),
                        "access"       => "General",
                        "addSpace"     => false,
                    ],
                ],
            ],
            "adds_space_between_classes" => [
                [ TestRouterSkipped::class, TestRouterRoutes::class ],
                [
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterSkipped",
                        "method"       => "otherParam",
                        "requestParam" => "",
                        "route"        => str_pad("\"/otherParam\"", $singlePad),
                        "access"       => "General",
                        "addSpace"     => true,
                    ],
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "basic",
                        "requestParam" => "",
                        "route"        => str_pad("\"/basic\"", $singlePad),
                        "access"       => "General",
                        "addSpace"     => false,
                    ],
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "withRequest",
                        "requestParam" => "\$request",
                        "route"        => str_pad("\"/request\"", $singlePad),
                        "access"       => "Admin",
                        "addSpace"     => false,
                    ],
                    [
                        "className"    => "\\Tests\\Builder\\TestRouterRoutes",
                        "method"       => "withCustom",
                        "requestParam" => "\\Tests\\Builder\\TestRouterRequest::fromRequest(\$request)",
                        "route"        => str_pad("\"/custom\"", $singlePad),
                        "access"       => "API",
                        "addSpace"     => false,
                    ],
                ],
            ],
        ];
    }


    public function testCollectRoutesIgnoresEmptyClass(): void {
        $result = RouterCode::collectRoutes([ new DiscoveryClass() ]);

        $this->assertSame([], $result["routes"]);
        $this->assertSame(0, $result["total"]);
        $this->assertFalse($result["hasRoutes"]);
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, RouterCode::destroyCode());
    }
}
