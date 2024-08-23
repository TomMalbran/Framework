<?php
// phpcs:ignoreFile
namespace Framework\IO;

/**
 * The Importer Data
 */
class ImporterData {

    /**
     * Creates a new ImporterData instance
     */
    public function __construct(
        /** @var string[] */
        public array  $columns = [],
        public int    $amount  = 0,
        public string $first   = "",
        public string $last    = "",
    ) {
    }
}
