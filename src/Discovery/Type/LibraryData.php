<?php
namespace Framework\Discovery\Type;

use Framework\Utils\Strings;

/**
 * The Library Data
 */
class LibraryData {

    public string $name = "";

    // The Tag of the Repository that is placed, which is a version that stays put
    public string $tag = "";

    // The Branch of the Repository that is placed, when there is no Tag. It moves,
    // so what came from one is placed again every time it is asked for
    public string $branch = "";

    // The Repository the Library is placed from
    public string $url = "";

    // The directory of the Repository that is copied, or all of it when empty
    public string $source = "";

    // Where the source is placed, beside the directory the composer file is in
    public string $path = "";



    /**
     * The Library Data
     * @param string $name   Optional.
     * @param string $tag    Optional.
     * @param string $branch Optional.
     * @param string $url    Optional.
     * @param string $source Optional.
     * @param string $path   Optional.
     */
    public function __construct(
        string $name = "",
        string $tag = "",
        string $branch = "",
        string $url = "",
        string $source = "",
        string $path = "",
    ) {
        $this->name   = $name;
        $this->tag    = $tag;
        $this->branch = $branch;
        $this->url    = $url;
        $this->source = $source;
        $this->path   = $path;
    }

    /**
     * Creates the Library Data from what the composer file of an App holds for it
     * @param mixed $name
     * @param mixed $data
     * @return LibraryData|null
     */
    public static function create(mixed $name, mixed $data): ?LibraryData {
        // Nothing but whoever copied the Libraries writes this, so the shape is
        // not to be trusted, and what is not one of them is not one
        if (!is_string($name) || $name === "" || !is_array($data)) {
            return null;
        }

        return new LibraryData(
            $name,
            Strings::toString($data["tag"] ?? ""),
            Strings::toString($data["branch"] ?? ""),
            Strings::toString($data["url"] ?? ""),
            Strings::toString($data["source"] ?? ""),
            Strings::toString($data["path"] ?? ""),
        );
    }

    /**
     * Returns true if the Library has everything it takes to be placed
     * @return bool
     */
    public function isValid(): bool {
        return $this->name !== "" && $this->url !== ""
            && $this->path !== "" && $this->getReference() !== "";
    }

    /**
     * Returns what the Library is placed from, which is the Tag before the Branch
     * @return string
     */
    public function getReference(): string {
        return $this->tag !== "" ? $this->tag : $this->branch;
    }

    /**
     * Returns the Url of the archive, as a repository serves a tag and a branch
     * @return string
     */
    public function getArchiveUrl(): string {
        if ($this->url === "") {
            return "";
        }

        $url = Strings::stripEnd($this->url, "/");
        if ($this->tag !== "") {
            return "{$url}/archive/refs/tags/{$this->tag}.zip";
        }
        if ($this->branch !== "") {
            return "{$url}/archive/refs/heads/{$this->branch}.zip";
        }
        return "";
    }

    /**
     * Returns a name for the Library that can be a directory
     * @return string
     */
    public function getFileName(): string {
        // A branch is written with slashes as often as not, and a path is not
        $reference = Strings::replace($this->getReference(), "/", "-");
        return "{$this->name}-{$reference}";
    }
}
