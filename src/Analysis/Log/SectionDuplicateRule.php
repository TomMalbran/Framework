<?php
namespace Framework\Analysis\Log;

use Framework\Analysis\Log\SectionNameCollector;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

use PhpParser\Node;

/**
 * The Section Duplicate Rule
 * @implements Rule<CollectedDataNode>
 */
class SectionDuplicateRule implements Rule {

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
        // Retrieve everything gathered by SectionNameCollector
        $data = $node->get(SectionNameCollector::class);
        ksort($data);

        $sections = [];
        $errors   = [];

        foreach ($data as $fileData) {
            foreach ($fileData as [ $section, $class, $file, $line ]) {
                if (isset($sections[$section])) {
                    $other    = $sections[$section];
                    $message  = "Duplicate section '$section' found in $other and $class.";
                    $errors[] = RuleErrorBuilder::message($message)
                        ->file($file)
                        ->line($line)
                        ->identifier("framework.sectionDuplicate")
                        ->build();
                    continue;
                }
                $sections[$section] = $class;
            }
        }

        return $errors;
    }
}
