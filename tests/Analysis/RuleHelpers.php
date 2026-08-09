<?php
namespace Tests\Analysis;

use PHPStan\Rules\Rule;

use ReflectionMethod;
use ReflectionNamedType;

/**
 * What the Rule tests have in common
 *
 * Each of them builds its rule from a data provider, since RuleTestCase keeps
 * the rule it was first given: a second analyse in the same test would quietly
 * run the first rule again, against a fixture it was never meant to see.
 */
trait RuleHelpers {

    private Rule $rule;


    #[\Override]
    protected function getRule(): Rule {
        return $this->rule;
    }

    /**
     * Builds the given Rule, reading what its constructor asks for
     *
     * The Reflection Provider is recognised by its type and the flag by its
     * name. Anything else a rule takes is given by the case that names it.
     * @param string      $ruleClass
     * @param bool        $enabled   Optional.
     * @param list<mixed> $extra     Optional.
     * @return Rule
     */
    private function makeRule(string $ruleClass, bool $enabled = true, array $extra = []): Rule {
        $arguments = [];

        if (method_exists($ruleClass, "__construct")) {
            foreach ((new ReflectionMethod($ruleClass, "__construct"))->getParameters() as $param) {
                $type = $param->getType();
                if ($param->getName() === "enabled") {
                    $arguments[] = $enabled;
                } elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $arguments[] = self::createReflectionProvider();
                } elseif (count($extra) > 0) {
                    $arguments[] = array_shift($extra);
                }
            }
        }

        /** @var Rule */
        return new $ruleClass(...$arguments);
    }

    /**
     * Returns the paths of the given fixtures of the group being tested
     * @param list<string> $names
     * @return list<string>
     */
    private function fixtures(array $names): array {
        $result = [];
        foreach ($names as $name) {
            $result[] = __DIR__ . "/Fixture/" . self::FixtureGroup . "/$name.php";
        }
        return $result;
    }
}
