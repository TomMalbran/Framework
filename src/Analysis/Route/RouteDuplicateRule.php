<?php
namespace Framework\Analysis\Route;

use Framework\Analysis\Route\RoutePathCollector;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;

/**
 * The Route Duplicate Rule
 * @implements Rule<CollectedDataNode>
 */
class RouteDuplicateRule implements Rule {

    /**
     * Returns the type of node this rule is interested in
     * @return class-string<CollectedDataNode>
     */
    #[\Override]
    public function getNodeType(): string {
        return CollectedDataNode::class;
    }

    /**
     * Processes the node and returns an array of errors if any
     * @param CollectedDataNode $node
     * @param Scope             $scope
     * @return list<IdentifierRuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array {
        // Retrieve everything gathered by RoutePathCollector
        $data = $node->get(RoutePathCollector::class);

        $paths  = [];
        $errors = [];

        foreach ($data as $fileData) {
            foreach ($fileData as [ $path, $method, $file, $line ]) {
                if (isset($paths[$path])) {
                    $message = "Duplicate route path '$path' found in {$paths[$path]} and $method.";
                    $errors[] = RuleErrorBuilder::message($message)
                        ->file($file)
                        ->line($line)
                        ->identifier("framework.routeDuplicate")
                        ->build();
                }
                $paths[$path] = $method;
            }
        }

        return $errors;
    }
}
