<?php
namespace Framework\Database;

/**
 * The Schema Merge
 */
class Merge {

    public string  $key    = "";
    public string  $glue   = "";

    /** @var string[] */
    public array   $fields = [];


    /**
     * Creates a new Merge instance
     * @param string              $key
     * @param array<string,mixed> $data
     */
    public function __construct(string $key, array $data) {
        $this->key    = $key;
        $this->glue   = !empty($data["mergeGlue"]) ? $data["mergeGlue"] : "";
        $this->fields = [];
    }
}
