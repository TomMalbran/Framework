<?php
namespace Tests\Analysis;

use Framework\Analysis\Enum\EnumHasNoneCaseRule;
use Framework\Analysis\Enum\EnumInternalMethodsRule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Enum rules
 * @extends RuleTestCase<Rule>
 */
class EnumRulesTest extends RuleTestCase {
    use RuleHelpers;

    private const FixtureGroup = "Enum";


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
     * @return array<string,array{string,list<string>,list<array{string,int}>}>
     */
    public static function providerRules(): array {
        $internal = "Use methods provided by IsEnum instead.";

        return [
            "a missing None case"          => [ EnumHasNoneCaseRule::class, [ "Shape" ], [
                [ "Enum 'Shape' is missing a 'None' case.", 8 ],
            ] ],
            "the None case is there"       => [ EnumHasNoneCaseRule::class, [ "Size" ], [] ],

            // Weekday has no IsEnum trait, so the rule does not apply to it
            "a plain enum"                 => [ EnumHasNoneCaseRule::class, [ "Weekday" ], [] ],

            // fromValue, which the trait provides, is what the rule points at
            // instead. A plain class with a cases() of its own is left alone,
            // since the rule goes by the type rather than by the name.
            "the php methods"              => [ EnumInternalMethodsRule::class, [ "EnumInternal" ], [
                [ "Usage of Enum::from() is disallowed. $internal", 7 ],
                [ "Usage of Enum::tryFrom() is disallowed. $internal", 8 ],
                [ "Usage of Enum::cases() is disallowed. $internal", 9 ],
            ] ],

            // Inside an enum that does not use IsEnum, the php methods are all there is
            "an enum using them on itself" => [ EnumInternalMethodsRule::class, [ "Weekday" ], [] ],
        ];
    }
}
