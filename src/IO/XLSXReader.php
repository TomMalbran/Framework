<?php
namespace Framework\IO;

use Framework\IO\ImporterReader;
use Framework\IO\ImporterData;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Sheet;
use OpenSpout\Reader\XLSX\RowIterator;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell\FormulaCell;

use Exception;

/**
 * The XLSX Reader
 */
class XLSXReader implements ImporterReader {

    private Reader       $reader;
    private ?Sheet       $sheet;
    private ?RowIterator $iterator = null;



    /**
     * Creates a new XLSXReader instance
     * @param string $path
     */
    public function __construct(string $path) {
        try {
            $options = new Options();
            $options->SHOULD_FORMAT_DATES = true;

            $this->reader = new Reader($options);
            $this->reader->open($path);
            $this->sheet = $this->reader->getSheetIterator()->current();
        } catch (Exception $e) {
            $this->sheet = null;
        }
    }

    /**
     * Returns true if the XLSXReader is available
     * @return boolean
     */
    public static function isAvailable(): bool {
        return class_exists(Reader::class);
    }



    /**
     * Returns true if the Reader is valid
     * @return boolean
     */
    public function isValid(): bool {
        return $this->sheet !== null;
    }

    /**
     * Returns some data
     * @param integer $amount Optional.
     * @return ImporterData
     */
    public function getData(int $amount = 3): ImporterData {
        $data = new ImporterData(
            columns: $this->getHeader(),
            amount:  0,
            first:   "",
            last:    "",
        );
        if ($this->sheet === null) {
            return $data;
        }

        foreach ($this->sheet->getRowIterator() as $index => $row) {
            if ($index < 2) {
                continue;
            }

            $values = $this->parseRow($row);
            $fields = [];
            for ($i = 0; $i < $amount; $i++) {
                if (!Arrays::isEmpty($values, $i)) {
                    $fields[] = $values[$i];
                }
            }

            if ($index === 2) {
                $data->first = Strings::join($fields, " - ");
            }
            $data->last    = Strings::join($fields, " - ");
            $data->amount += 1;
        }

        $this->reader->close();
        return $data;
    }

    /**
     * Returns the Header
     * @return Select[]
     */
    public function getHeader(): array {
        $columns = [];
        if ($this->sheet === null) {
            return $columns;
        }

        $iterator = $this->sheet->getRowIterator();
        $iterator->rewind();
        $headerRow = $this->parseRow($iterator->current());

        foreach ($headerRow as $key => $value) {
            if ($value !== "") {
                $columns[] = new Select((int)$key + 1, $value);
            }
        }
        return $columns;
    }

    /**
     * Returns the Content of the Row
     * @param Row $row
     * @return string[]
     */
    private function parseRow(Row $row): array {
        $cells  = $row->getCells();
        $result = [];
        foreach ($cells as $cell) {
            if ($cell instanceof FormulaCell) {
                $result[] = $cell->getComputedValue();
            } else {
                $result[] = $cell->getValue();
            }
        }
        return Arrays::toStrings($result);
    }



    /**
     * Starts the Iterator
     * @return void
     */
    public function rewind(): void {
        if ($this->sheet === null) {
            return;
        }

        $this->iterator = $this->sheet->getRowIterator();
        $this->iterator->rewind();
        $this->iterator->next();
    }

    /**
     * Returns the current Row
     * @return string[]
     */
    public function current(): array {
        if ($this->iterator !== null) {
            return $this->parseRow($this->iterator->current());
        }
        return [];
    }

    /**
     * Returns the current Key
     * @return integer
     */
    public function key(): int {
        if ($this->iterator !== null) {
            return $this->iterator->key();
        }
        return 0;
    }

    /**
     * Moves to the next Row
     * @return void
     */
    public function next(): void {
        if ($this->iterator !== null) {
            $this->iterator->next();
        }
    }

    /**
     * Returns true if the current Row is valid
     * @return boolean
     */
    public function valid(): bool {
        if ($this->iterator !== null) {
            return $this->iterator->valid();
        }
        return false;
    }
}
