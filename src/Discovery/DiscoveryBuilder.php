<?php
namespace Framework\Discovery;

/**
 * The Discovery Builder
 */
interface DiscoveryBuilder {

    /**
     * Resets the Code
     * @return string The path to write.
     */
    public static function resetCode(): string;

    /**
     * Generates the code
     * @return integer The amount of files created
     */
    public static function generateCode(): int;

}
