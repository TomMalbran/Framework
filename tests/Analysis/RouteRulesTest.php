<?php
namespace Tests\Analysis;

use Framework\Analysis\Route\RouteDuplicateRule;
use Framework\Analysis\Route\RouteMethodNameRule;
use Framework\Analysis\Route\RouteParamTypeRule;
use Framework\Analysis\Route\RoutePathCollector;
use Framework\Analysis\Route\RouteReturnTypeRule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Route rules
 * @extends RuleTestCase<Rule>
 */
class RouteRulesTest extends RuleTestCase {
    use RuleHelpers;

    private const FixtureGroup = "Route";


    /**
     * The duplicate rule reads what the collector gathered
     * @return list<Collector>
     */
    #[\Override]
    protected function getCollectors(): array {
        return [ new RoutePathCollector() ];
    }


    /**
     * A rule, the files it is run over, and what it has to say about them
     * @param string                  $ruleClass
     * @param list<string>            $fixtures
     * @param list<array{string,int}> $errors
     * @return void
     */
    #[DataProvider("providerRules")]
    public function testTheRuleReportsWhatItIsFor(
        string $ruleClass,
        array $fixtures,
        array $errors,
    ): void {
        $this->rule = $this->makeRule($ruleClass);

        $this->analyse($this->fixtures($fixtures), $errors);
    }

    /**
     * The Routes fixture holds four, and each rule has something to say about
     * one of them and nothing about the other three
     * @return array<string,array{string,list<string>,list<array{string,int}>}>
     */
    public static function providerRules(): array {
        $class = "Tests\\Analysis\\Fixture\\Route\\Routes";

        return [
            "the method name"         => [ RouteMethodNameRule::class, [ "Routes" ], [
                [ "Method {$class}::renamed() must match the route 'edit'.", 16 ],
            ] ],

            "the param type"          => [ RouteParamTypeRule::class, [ "Routes" ], [
                [
                    "Method {$class}::remove() must have no params or a Request " .
                    "param since is a Route.",
                    26,
                ],
            ] ],

            "the return type"         => [ RouteReturnTypeRule::class, [ "Routes" ], [
                [ "Method {$class}::view() must return a Response since is a Route.", 21 ],
            ] ],

            // The third route has a path of its own, so it is not named here
            "two methods on one path" => [ RouteDuplicateRule::class, [ "DuplicateRoutes" ], [
                [
                    "Duplicate route path '/crates/create' found in " .
                    "Tests\\Analysis\\Fixture\\Route\\DuplicateRoutes::create and " .
                    "Tests\\Analysis\\Fixture\\Route\\DuplicateRoutes::createAgain.",
                    16,
                ],
            ] ],
        ];
    }
}
