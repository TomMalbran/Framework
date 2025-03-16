<?php
namespace Framework\IO;

use Framework\Utils\Select;

/**
 * The Importer Data
 */
class ImporterData {

    /** @var Select[] */
    public array  $columns = [];
    public int    $amount  = 0;
    public string $first   = "";
    public string $last    = "";



    /**
     * Creates a new ImporterData instance
     * @param Select[] $columns
     * @param integer  $amount
     * @param string   $first
     * @param string   $last
     */
    public function __construct(
        array  $columns = [],
        int    $amount  = 0,
        string $first   = "",
        string $last    = "",
    ) {
        $this->columns = $columns;
        $this->amount  = $amount;
        $this->first   = $first;
        $this->last    = $last;
    }
}
