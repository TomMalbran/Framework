<?php
namespace Tests\Analysis;

use Framework\Analysis\General\DisallowClassCompareRule;
use Framework\Analysis\General\DisallowDebugPrintRule;
use Framework\Analysis\General\DisallowEmptyArrayRule;
use Framework\Analysis\General\DisallowEnumNameValueRule;
use Framework\Analysis\General\DisallowIntFloatWideningRule;
use Framework\Analysis\General\DisallowNativeDateRule;
use Framework\Analysis\General\EnumSwitchExhaustiveRule;
use Framework\Analysis\General\MethodReturnNotNeededRule;
use Framework\Analysis\General\PreferListTypeRule;
use Framework\Analysis\General\PreferMatchOverSwitchRule;
use Framework\Analysis\General\PrivateMethodReturnUnusedRule;
use Framework\Analysis\General\RequireNamedBoolArgRule;
use Framework\Analysis\General\RequireOptionalCommentRule;
use Framework\Analysis\General\RequireReturnTypeRule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The General rules
 * @extends RuleTestCase<Rule>
 */
class GeneralRulesTest extends RuleTestCase {
    use RuleHelpers;

    private const FixtureGroup = "General";


    /**
     * A rule, a file it has something to say about, and what it says
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
     * Each fixture holds the cases the rule is meant to catch and the ones it
     * is meant to leave alone, so an empty line list would not prove much
     * @return array<string,array{string,list<string>,list<array{string,int}>}>
     */
    public static function providerRules(): array {
        $emptyArray = "Empty array shapes 'array{}' are disallowed. " .
            "Use a specific type like 'array<mixed, mixed>' instead.";
        $intFloat   = "Parameter 'amount' expects float, but integer passed.";

        return [
            // Comparing their values, or comparing against $this, is left alone
            "class compare"              => [ DisallowClassCompareRule::class, [ "ClassCompare" ], [
                [ "Direct comparison between classes 'Box' and 'Box' is disallowed.", 7 ],
                [ "Direct comparison between classes 'Box' and 'Box' is disallowed.", 11 ],
            ] ],

            "debug print"                => [ DisallowDebugPrintRule::class, [ "DebugPrint" ], [
                [ "Usage of var_dump() is disallowed. Remove debug calls.", 7 ],
                [ "Usage of print_r() is disallowed. Remove debug calls.", 8 ],
            ] ],

            "empty array"                => [ DisallowEmptyArrayRule::class, [ "EmptyArray" ], [
                [ $emptyArray, 8 ],
                [ $emptyArray, 9 ],
            ] ],

            "enum name and value"        => [ DisallowEnumNameValueRule::class, [ "EnumNameValue" ], [
                [ "Accessing '->name' on Enums is disallowed. Use '->toString()' instead.", 15 ],
                [ "Accessing '->value' on Enums is disallowed. Use '->toString()' instead.", 16 ],
            ] ],

            // Through a method call, a static call and a named argument. Left
            // alone when a float is passed, when one is handed straight through,
            // and when the parameter says an integer is welcome
            "int given to a float"       => [ DisallowIntFloatWideningRule::class, [ "IntFloatWidening" ], [
                [ $intFloat, 20 ],
                [ $intFloat, 24 ],
                [ $intFloat, 28 ],
            ] ],

            "native date"                => [ DisallowNativeDateRule::class, [ "NativeDate" ], [
                [ "Usage of time() is disallowed. Use the Date class instead.", 7 ],
                [ "Usage of date() is disallowed. Use the Date class instead.", 8 ],
            ] ],

            // The switch below it covers all three, so it is left alone
            "a missing case"             => [ EnumSwitchExhaustiveRule::class, [ "SwitchExhaustive" ], [
                [ "Switch on Size is missing cases: Large.", 9 ],
            ] ],

            "the other switch problems"  => [ EnumSwitchExhaustiveRule::class, [ "SwitchProblems" ], [
                [ "The 'None' case must be the first case in the switch.", 14 ],
                [
                    "Case type Tests\\Analysis\\Fixture\\Enum\\Grade::Pass " .
                    "does not match enum Tests\\Analysis\\Fixture\\Enum\\Size.",
                    27,
                ],
                [
                    "Switch on Tests\\Analysis\\Fixture\\Enum\\Size uses a default " .
                    "for the only missing case 'None'.",
                    40,
                ],
            ] ],

            // Found through a switch, a try and an else as well as plainly. Left
            // alone when it can return either, when it is not a boolean, when it
            // is a single statement, and when it was inherited rather than written
            "a boolean always the same"  => [ MethodReturnNotNeededRule::class, [ "ReturnNotNeeded" ], [
                [ "Method reported always returns true.", 6 ],
                [ "Method reportedThroughBlocks always returns true.", 14 ],
            ] ],

            "the bracket suffix"         => [ PreferListTypeRule::class, [ "ListType" ], [
                [ "Use 'list<string>' instead of 'string[]'.", 6 ],
                [ "Use 'list<string>' instead of 'string[]'.", 11 ],
                [ "Use 'list<int>' instead of 'int[]'.", 12 ],
            ] ],

            // The one further down does work in each arm, so it stays a switch
            "a switch that only returns" => [ PreferMatchOverSwitchRule::class, [ "MatchOverSwitch" ], [
                [ "This switch statement can be refactored to a match expression.", 7 ],
            ] ],

            // Caught through a method call and a static one. The three whose
            // results are assigned or tested are left alone
            "a return nobody reads"      => [ PrivateMethodReturnUnusedRule::class, [ "PrivateReturnUnused" ], [
                [ "Private method 'reported' returns a value, but is never used.", 19 ],
                [ "Private method 'reportedStatically' returns a value, but is never used.", 23 ],
            ] ],

            "a positional boolean"       => [ RequireNamedBoolArgRule::class, [ "NamedBoolArg" ], [
                [
                    "Boolean argument #2 (true) must be named to improve readability " .
                    "(e.g., paramName: true).",
                    11,
                ],
            ] ],

            "a param with a default"     => [ RequireOptionalCommentRule::class, [ "OptionalComment" ], [
                [ "Optional parameter '\$amount' must have a description as 'Optional.'", 9 ],
            ] ],

            // A plain function is described differently from a method. Constructors,
            // closures and arrow functions are never asked for a return type
            "a missing return type"      => [ RequireReturnTypeRule::class, [ "ReturnType" ], [
                [ "Function reportedFunction() is missing a native return type hint.", 5 ],
                [ "Method reported() is missing a native return type hint.", 15 ],
                [ "Method alsoReported() is missing a native return type hint.", 19 ],
            ] ],
        ];
    }


    /**
     * A rule with its flag off has nothing to say
     * @param string       $ruleClass
     * @param list<string> $fixtures
     * @return void
     */
    #[DataProvider("providerRules")]
    public function testAFlaggedRuleGoesQuietWhenOff(string $ruleClass, array $fixtures): void {
        $this->rule = $this->makeRule($ruleClass, enabled: false);

        $this->analyse($this->fixtures($fixtures), []);
    }
}
