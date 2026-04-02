<?php
namespace Framework\ImpExp;

use Framework\IO\Select;
use Framework\ImpExp\ImporterData;

use Iterator;

/**
 * The Importer Reader
 * @extends Iterator<int,string[]>
 */
interface ImporterReader extends Iterator {

    /**
     * Returns true if the Reader is valid
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Returns some data
     * @param int $amount Optional.
     * @return ImporterData
     */
    public function getData(int $amount = 3): ImporterData;

    /**
     * Returns the Header
     * @return list<Select>
     */
    public function getHeader(): array;
}
