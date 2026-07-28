<?php
namespace Framework\Analysis\Log;

use Framework\Log\Attr\Section;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

/**
 * The Section Name Collector
 * @implements Collector<InClassNode,array{string,string,string,int}>
 */
class SectionNameCollector implements Collector {

    /**
     * Returns the type of node this collector is interested in
     * @return class-string<InClassNode>
     */
    #[\Override]
    public function getNodeType(): string {
        return InClassNode::class;
    }

    /**
     * Processes the node and returns an array of collected data if any
     * @param InClassNode $node
     * @param Scope       $scope
     * @return array{string,string,string,int}|null
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array|null {
        $className = $node->getClassReflection()->getName();

        // Find the Section attribute
        foreach ($node->getOriginalNode()->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($scope->resolveName($attr->name) !== Section::class) {
                    continue;
                }

                // Get the name (first argument)
                $nameArg = $attr->args[0] ?? null;
                if ($nameArg === null || !$nameArg->value instanceof String_) {
                    continue;
                }

                return [
                    $nameArg->value->value,
                    $className,
                    $scope->getFile(),
                    $attrGroup->getLine(),
                ];
            }
        }

        return null;
    }
}
