<?php
namespace Framework\IO;

use Framework\IO\ImporterData;
use Framework\Utils\Select;

use Iterator;

/**
 * The Importer Reader
 */
interface ImporterReader extends Iterator {

    /**
     * Returns true if the Reader is valid
     * @return boolean
     */
    public function isValid(): bool;

    /**
     * Returns some data
     * @param integer $amount
     * @return ImporterData
     */
    public function getData(int $amount = 3): ImporterData;

    /**
     * Returns the Header
     * @return Select[]
     */
    public function getHeader(): array;
}
