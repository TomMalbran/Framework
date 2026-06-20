<?php
namespace Framework\Analysis\General;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Reflection\ParametersAcceptorSelector;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\FuncCall;

/**
 * The Disallow Int To Float Widening Rule
 * @implements Rule<CallLike>
 */
class DisallowIntFloatWideningRule implements Rule {

    /**
     * Creates a new Disallow Int Float Widening Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<CallLike>
     */
    #[\Override]
    public function getNodeType(): string {
        return CallLike::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param CallLike $node
     * @param Scope    $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        // Only process method, static, or function calls
        if (!$node instanceof MethodCall &&
            !$node instanceof StaticCall &&
            !$node instanceof FuncCall
        ) {
            return [];
        }

        $errors = [];

        // 1. Get the function/method reflection to find parameter types
        try {
            if ($node instanceof MethodCall || $node instanceof StaticCall) {
                $methodName = $node->name;
                if (!$methodName instanceof Node\Identifier) {
                    return [];
                }

                if ($node instanceof MethodCall) {
                    $callerType = $scope->getType($node->var);
                } else {
                    $classNode = $node->class;
                    if ($classNode instanceof Name) {
                        $callerType = $scope->resolveTypeByName($classNode);
                    } else {
                        $callerType = $scope->getType($classNode);
                    }
                }

                if (!$callerType->hasMethod($methodName->name)->yes()) {
                    return [];
                }
                $reflection = $callerType->getMethod($methodName->name, $scope);
            } else {
                if (!$node->name instanceof Name) {
                    return [];
                }
                $reflection = $scope->getFunction();
            }
            if ($reflection === null) {
                return [];
            }

            $variant = ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $reflection->getVariants(),
            );
            $parameters = $variant->getParameters();
        } catch (\Exception $e) {
            return [];
        }


        // 2. Compare passed arguments to expected parameters
        foreach ($node->getArgs() as $i => $arg) {
            // Find the correct parameter by name (for named args) or by index
            $parameter = null;
            if ($arg->name !== null) {
                foreach ($parameters as $p) {
                    if ($p->getName() === $arg->name->name) {
                        $parameter = $p;
                        break;
                    }
                }
            } else {
                $parameter = $parameters[$i] ?? null;
            }
            if ($parameter === null) {
                continue;
            }

            $expectedType = $parameter->getType();
            $passedType   = $scope->getType($arg->value);

            // Does the parameter expect a float?
            // We check if FloatType is part of the union/type.
            $expectsFloat = false;
            foreach (TypeUtils::flattenTypes($expectedType) as $type) {
                if ($type->isFloat()->yes()) {
                    $expectsFloat = true;
                    break;
                }
            }

            // Does the parameter explicitly allow an integer?
            $expectsInt = false;
            foreach (TypeUtils::flattenTypes($expectedType) as $type) {
                if ($type->isInteger()->yes()) {
                    $expectsInt = true;
                    break;
                }
            }

            // Is the passed value an integer?
            $passesIntPart = false;
            foreach (TypeUtils::flattenTypes($passedType) as $type) {
                if ($type->isInteger()->yes()) {
                    $passesIntPart = true;
                    break;
                }
            }

            // ERROR: Passing an int to a slot that wants float but doesn't explicitly allow int.
            if ($passesIntPart && $expectsFloat && !$expectsInt) {
                $paramName = $parameter->getName();
                $typeName  = $expectedType->describe(VerbosityLevel::typeOnly());
                $shortType = preg_replace('/[a-zA-Z0-9_]+\\\\/', '', $typeName);

                $errors[] = RuleErrorBuilder::message(
                    "Parameter '$paramName' expects $shortType, but integer passed."
                )
                ->line($arg->getLine())
                ->identifier("framework.typeWidening")
                ->build();
            }
        }

        return $errors;
    }
}
