<?php
namespace Framework\Analysis\General;

use Framework\Utils\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;

/**
 * The Private Method Return Unused Rule
 * @implements Rule<Class_>
 */
class PrivateMethodReturnUnusedRule implements Rule {

    /**
     * Create a new Private Method Return Unused Rule
     * @param bool $enabled Optional.
     */
    public function __construct(
        private bool $enabled = false,
    ) {
    }

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<Class_>
     */
    #[\Override]
    public function getNodeType(): string {
        return Class_::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param Class_ $node
     * @param Scope  $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        if (!$this->enabled) {
            return [];
        }

        $privateMethods = [];

        // Collect all private methods that return something (not void)
        foreach ($node->getMethods() as $method) {
            if (!$method->isPrivate() || $method->name->toString() === "__construct") {
                continue;
            }

            // Simple check: if it has a return type and it's not void
            if ($method->returnType instanceof Identifier && $method->returnType->toLowerString() === "void") {
                continue;
            }

            $privateMethods[$method->name->toString()] = [
                "node"           => $method,
                "hasUsedCall"    => false,
                "hasIgnoredCall" => false,
            ];
        }

        if (count($privateMethods) === 0) {
            return [];
        }

        // Recursively scan the class body for calls
        $this->scanUsage($node, $privateMethods);

        $errors = [];
        foreach ($privateMethods as $name => $data) {
            // Error only if the method was called, but only as standalone expressions
            if (!$data["hasIgnoredCall"] || $data["hasUsedCall"]) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message("Private method '{$name}' returns a value, but is never used.")
                ->line($data["node"]->getLine())
                ->identifier("general.unusedPrivateReturn")
                ->build();
        }

        return $errors;
    }

    /**
     * Recursively scans a node for method calls to the private methods we're tracking
     * @param Node                                                                       $node
     * @param array<string,array{node:ClassMethod,hasUsedCall:bool,hasIgnoredCall:bool}> $privateMethods
     * @param Node|null                                                                  $parent         Optional.
     * @return void
     */
    private function scanUsage(Node $node, array &$privateMethods, ?Node $parent = null): void {
        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $name = $node->name->toString();
            // Check for $this->method()
            if (isset($privateMethods[$name]) && $node->var instanceof Variable && $node->var->name === "this") {
                if ($parent instanceof Expression) {
                    $privateMethods[$name]["hasIgnoredCall"] = true;
                } else {
                    $privateMethods[$name]["hasUsedCall"] = true;
                }
            }
        }

        if ($node instanceof StaticCall && $node->name instanceof Identifier) {
            $name = $node->name->toString();
            // Check for self::method() or static::method()
            if (isset($privateMethods[$name]) && $node->class instanceof Name &&
                Arrays::contains([ "self", "static" ], $node->class->toLowerString())
            ) {
                if ($parent instanceof Expression) {
                    $privateMethods[$name]["hasIgnoredCall"] = true;
                } else {
                    $privateMethods[$name]["hasUsedCall"] = true;
                }
            }
        }

        // Standard AST traversal
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $this->scanUsage($item, $privateMethods, $node);
                    }
                }
            } elseif ($subNode instanceof Node) {
                $this->scanUsage($subNode, $privateMethods, $node);
            }
        }
    }
}
