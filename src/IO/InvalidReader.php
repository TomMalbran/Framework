<?php
namespace Framework\IO;

use Framework\IO\ImporterReader;
use Framework\Utils\Select;

/**
 * The Invalid Reader
 */
class InvalidReader implements ImporterReader {

    /**
     * Returns true if the Reader is valid
     * @return bool
     */
    #[\Override]
    public function isValid(): bool {
        return false;
    }

    /**
     * Returns some data
     * @param int $amount
     * @return ImporterData
     */
    #[\Override]
    public function getData(int $amount = 0): ImporterData {
        return new ImporterData(
            columns: [],
            amount:  0,
            first:   "",
            last:    "",
        );
    }

    /**
     * Returns the Header
     * @return Select[]
     */
    #[\Override]
    public function getHeader(): array {
        return [];
    }



    /**
     * Starts the Iterator
     * @return void
     */
    #[\Override]
    public function rewind(): void {
        // Nothing to do
    }

    /**
     * Returns the current Row
     * @return string[]
     */
    #[\Override]
    public function current(): array {
        return [];
    }

    /**
     * Returns the current Key
     * @return int
     */
    #[\Override]
    public function key(): int {
        return 0;
    }

    /**
     * Moves to the next Row
     * @return void
     */
    #[\Override]
    public function next(): void {
        // Nothing to do
    }

    /**
     * Returns true if the current Row is valid
     * @return bool
     */
    #[\Override]
    public function valid(): bool {
        return false;
    }
}
