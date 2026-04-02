<?php
namespace Framework\ImpExp;

use Framework\IO\Select;
use Framework\ImpExp\ImporterReader;
use Framework\ImpExp\ImporterData;
use Framework\Utils\Arrays;
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
     * @return bool
     */
    public static function isAvailable(): bool {
        return class_exists(Reader::class);
    }



    /**
     * Returns true if the Reader is valid
     * @return bool
     */
    #[\Override]
    public function isValid(): bool {
        return $this->sheet !== null;
    }

    /**
     * Returns some data
     * @param int $amount Optional.
     * @return ImporterData
     */
    #[\Override]
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

        $lastRow = null;
        foreach ($this->sheet->getRowIterator() as $index => $row) {
            if ($index < 2) {
                continue;
            }

            if ($index === 2) {
                $data->first = $this->getRowAsString($row, $amount);
                $data->last  = $data->first;
            }
            $data->amount += 1;

            $lastRow = $row;
        }
        if ($lastRow !== null) {
            $data->last = $this->getRowAsString($lastRow, $amount);
        }

        $this->reader->close();
        return $data;
    }

    /**
     * Returns the Header
     * @return list<Select>
     */
    #[\Override]
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
                $columns[] = new Select($key + 1, $value);
            }
        }
        return $columns;
    }

    /**
     * Returns the Content of the Row
     * @param Row $row
     * @param int $amount Optional.
     * @return list<string>
     */
    private function parseRow(Row $row, int $amount = 0): array {
        $cells  = $row->getCells();
        $index  = 0;
        $result = [];

        foreach ($cells as $cell) {
            if ($cell instanceof FormulaCell) {
                $result[] = Strings::toString($cell->getComputedValue());
            } else {
                $result[] = Strings::toString($cell->getValue());
            }

            if ($amount > 0 && $index >= $amount - 1) {
                break;
            }
            $index += 1;
        }
        return $result;
    }

    /**
     * Returns the Row as String
     * @param Row $row
     * @param int $amount Optional.
     * @return string
     */
    private function getRowAsString(Row $row, int $amount = 0): string {
        $values = $this->parseRow($row, $amount);
        $fields = [];
        for ($i = 0; $i < $amount; $i += 1) {
            if (isset($values[$i]) && !Arrays::isEmpty($values, $i)) {
                $fields[] = $values[$i];
            }
        }
        return Strings::join($fields, " - ");
    }



    /**
     * Starts the Iterator
     * @return void
     */
    #[\Override]
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
     * @return list<string>
     */
    #[\Override]
    public function current(): array {
        if ($this->iterator !== null) {
            return $this->parseRow($this->iterator->current());
        }
        return [];
    }

    /**
     * Returns the current Key
     * @return int
     */
    #[\Override]
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
    #[\Override]
    public function next(): void {
        if ($this->iterator !== null) {
            $this->iterator->next();
        }
    }

    /**
     * Returns true if the current Row is valid
     * @return bool
     */
    #[\Override]
    public function valid(): bool {
        if ($this->iterator !== null) {
            return $this->iterator->valid();
        }
        return false;
    }
}
