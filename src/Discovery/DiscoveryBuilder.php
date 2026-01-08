<?php
namespace Framework\Discovery;

/**
 * The Discovery Builder
 */
interface DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer The amount of files created.
     */
    public static function generateCode(): int;

    /**
     * Destroys the Code
     * @return integer The amount of files deleted.
     */
    public static function destroyCode(): int;

}
