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

    // The Libraries copied into the Source, keyed by their name
    /** @var array<string,LibraryData> */
    public array $libraries = [];



    /**
     * The Composer Data
     * @param string                    $name      Optional.
     * @param string                    $version   Optional.
     * @param string                    $namespace Optional.
     * @param string                    $sourceDir Optional.
     * @param array<string,LibraryData> $libraries Optional.
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
     * Returns the given Library copied into the Source
     * @param string $name
     * @return LibraryData|null
     */
    public function getLibrary(string $name): ?LibraryData {
        return $this->libraries[$name] ?? null;
    }

    /**
     * Returns the Tag or the Branch the given Library was copied from
     * @param string $name
     * @return string
     */
    public function getLibraryVersion(string $name): string {
        $library = $this->getLibrary($name);
        return $library !== null ? $library->getReference() : "";
    }
}
