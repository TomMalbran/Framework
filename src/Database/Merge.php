<?php
namespace Framework\Database;

/**
 * The Schema Merge
 */
class Merge {

    public string $key    = "";
    public string $glue   = "";

    /** @var string[] */
    public array  $fields = [];


    /**
     * Creates a new Merge instance
     * @param string $key
     * @param string $glue
     */
    public function __construct(string $key, string $glue) {
        $this->key    = $key;
        $this->glue   = $glue;
        $this->fields = [];
    }
}
