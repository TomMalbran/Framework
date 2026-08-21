<?php
namespace Framework\Discovery\Type;

/**
 * The Composer Data
 */
class ComposerData {

    public string $name      = "";
    public string $version   = "";
    public string $namespace = "";
    public string $sourceDir = "";

    // The Versions of the Libraries copied into the Source
    /** @var array<string,string> */
    public array $libraries = [];



    /**
     * The Composer Data
     * @param string               $name      Optional.
     * @param string               $version   Optional.
     * @param string               $namespace Optional.
     * @param string               $sourceDir Optional.
     * @param array<string,string> $libraries Optional.
     */
    public function __construct(
        string $name = "",
        string $version = "",
        string $namespace = "",
        string $sourceDir = "",
        array $libraries = [],
    ) {
        $this->name      = $name;
        $this->version   = $version;
        $this->namespace = $namespace;
        $this->sourceDir = $sourceDir;
        $this->libraries = $libraries;
    }

    /**
     * Returns the Version of the given Library copied into the Source
     * @param string $name
     * @return string
     */
    public function getLibraryVersion(string $name): string {
        return $this->libraries[$name] ?? "";
    }
}
