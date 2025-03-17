<?php
namespace Framework\IO;

use Framework\IO\ImporterReader;
use Framework\IO\ImporterData;
use Framework\IO\ImporterRow;
use Framework\IO\XLSXReader;
use Framework\Utils\Select;

use Iterator;

/**
 * The Importer Wrapper
 * @implements Iterator<integer,ImporterRow>
 */
class Importer implements Iterator {

    private ImporterReader $reader;

    /** @var array<string,integer> */
    private array          $columns = [];



    /**
     * Creates a new Importer instance
     * @param string $path
     */
    public function __construct(string $path) {
        if (XLSXReader::isAvailable()) {
            $this->reader = new XLSXReader($path);
        } else {
            // $this->reader = new CSVReader($path);
        }
    }

    /**
     * Returns true if the Importer is valid
     * @return boolean
     */
    public function isValid(): bool {
        return $this->reader->isValid();
    }

    /**
     * Returns some data
     * @param integer $amount
     * @return ImporterData
     */
    public function getData(int $amount = 3): ImporterData {
        return $this->reader->getData($amount);
    }

    /**
     * Returns the Header
     * @return Select[]
     */
    public function getHeader(): array {
        return $this->reader->getHeader();
    }

    /**
     * Sets the Columns
     * @param array<string,integer> ...$columnsList
     * @return Importer
     */
    public function setColumns(array ...$columnsList): Importer {
        foreach ($columnsList as $columns) {
            foreach ($columns as $key => $index) {
                $this->columns[$key] = $index;
            }
        }
        return $this;
    }

    /**
     * Sets the Column Names
     * @param string ...$columnsKeys
     * @return Importer
     */
    public function setColumnNames(string ...$columnsKeys): Importer {
        foreach ($columnsKeys as $index => $key) {
            $this->columns[$key] = (int)$index + 1;
        }
        return $this;
    }



    /**
     * Starts the Iterator
     * @return void
     */
    public function rewind(): void {
        $this->reader->rewind();
    }

    /**
     * Returns the current Row
     * @return ImporterRow
     */
    public function current(): ImporterRow {
        $data = $this->reader->current();
        return new ImporterRow($data, $this->columns);
    }

    /**
     * Returns the current Key
     * @return integer
     */
    public function key(): int {
        return $this->reader->key();
    }

    /**
     * Moves to the next Row
     * @return void
     */
    public function next(): void {
        $this->reader->next();
    }

    /**
     * Returns true if the current Row is valid
     * @return boolean
     */
    public function valid(): bool {
        return $this->reader->valid();
    }
}
