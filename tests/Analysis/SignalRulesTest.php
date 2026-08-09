<?php
namespace Tests\Analysis;

use Framework\Analysis\Signal\ListenerReturnVoidRule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Signal rules
 * @extends RuleTestCase<Rule>
 */
class SignalRulesTest extends RuleTestCase {
    use RuleHelpers;

    private const FixtureGroup = "Signal";


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
     * The one returning void, and the one without the attribute, are fine
     * @return array<string,array{string,list<string>,list<array{string,int}>}>
     */
    public static function providerRules(): array {
        return [
            "a listener returning something" => [ ListenerReturnVoidRule::class, [ "Listeners" ], [
                [ "Method with #[Listener] attribute must return 'void'.", 8 ],
            ] ],
        ];
    }
}
