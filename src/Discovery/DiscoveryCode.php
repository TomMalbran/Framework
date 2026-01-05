<?php
namespace Framework\Discovery;

/**
 * The Discovery Code
 */
interface DiscoveryCode {

    /**
     * Returns the File Name to Generate
     * @return string
     */
    public static function getFileName(): string;

    /**
     * Returns the File Code to Generate
     * @return array<string,mixed>
     */
    public static function getFileCode(): array;

}
