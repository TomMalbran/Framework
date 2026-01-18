<?php
namespace Framework\Discovery;

/**
 * The Discovery Builder
 */
interface DiscoveryBuilder {

    /**
     * Generates the code
     * @return int The amount of files created.
     */
    public static function generateCode(): int;

    /**
     * Destroys the Code
     * @return int The amount of files deleted.
     */
    public static function destroyCode(): int;

}
