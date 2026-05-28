<?php
namespace Framework\Analysis\Database;

use Framework\Database\Query\Query;
use Framework\Database\Query\Operator;
use Framework\Utils\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;

/**
 * The Query Argument Rule
 * @implements Rule<Node\Expr>
 */
class QueryArgumentRule implements Rule {

    /**
     * Creates the Query Argument Rule
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Expr>
     */
    #[\Override]
    public function getNodeType(): string {
        return Expr::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Expr  $node
     * @param Scope $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        // Only target StaticCall or MethodCall
        if (!$node instanceof StaticCall && !$node instanceof MethodCall) {
            return [];
        }

        // Resolve the class type safely
        if ($node instanceof StaticCall) {
            // If it's a Name (Query::method), convert to a Fetch to get the type
            $classNode = $node->class;
            if ($classNode instanceof Name) {
                $classType = $scope->resolveTypeByName($classNode);
            } else {
                $classType = $scope->getType($classNode);
            }
        } else {
            // MethodCall is always an Expr
            $classType = $scope->getType($node->var);
        }

        // Check that the class type is Query
        $classNames   = $classType->getObjectClassNames();
        $isQueryClass = false;
        foreach ($classNames as $className) {
            if ($this->reflectionProvider->hasClass($className) && $className === Query::class) {
                $isQueryClass = true;
                break;
            }
        }
        if (!$isQueryClass) {
            return [];
        }

        $methodName = $node->name instanceof Identifier ? $node->name->toString() : "";
        $args       = $node->getArgs();
        $errors     = [];

        // Check Static Methods (select/update)
        if ($node instanceof StaticCall && ($methodName === "select" || $methodName === "update")) {
            if (isset($args[1]) && $args[1]->name === null && !$this->isValidName($args[1], "as")) {
                $errors[] = RuleErrorBuilder::message(
                    "The 'as' parameter in Query::{$methodName} must be a named argument."
                )
                    ->line($node->getLine())
                    ->identifier("framework.queryNamedAs")
                    ->build();
            }
        }

        // Check Instance Methods (join/where)
        if (!$node instanceof MethodCall) {
            return $errors;
        }
        if ($methodName === "join") {
            if (isset($args[1]) && $args[1]->name === null && !$this->isValidName($args[1], "as")) {
                $errors[] = RuleErrorBuilder::message("The 'as' parameter in Query->join must be a named argument.")
                    ->line($node->getLine())
                    ->identifier("framework.queryNamedAs")
                    ->build();
            }

            if (isset($args[2]) && $args[2]->name === null && !$this->isValidName($args[2], "on")) {
                $errors[] = RuleErrorBuilder::message("The 'on' parameter in Query->join must be a named argument.")
                    ->line($node->getLine())
                    ->identifier("framework.queryNamedOn")
                    ->build();
            }
        }

        if ($methodName === "where" && isset($args[1])) {
            $argType         = $scope->getType($args[1]->value);
            $constantStrings = $argType->getConstantStrings();
            $validOperators  = Operator::getNames();

            // Only proceed if we have a definite constant string value
            foreach ($constantStrings as $constantString) {
                $value = $constantString->getValue();
                if (Arrays::contains($validOperators, $value)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message("The value '{$value}' is not a valid Operator.")
                    ->line($node->getLine())
                    ->identifier("framework.queryInvOpp")
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Checks if a positional argument is a variable matching the expected name.
     * @param Arg    $arg
     * @param string $expectedName
     * @return bool
     */
    private function isValidName(Arg $arg, string $expectedName): bool {
        return $arg->value instanceof Variable &&
            is_string($arg->value->name) &&
            $arg->value->name === $expectedName;
    }
}
