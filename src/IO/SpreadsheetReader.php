<?php
namespace Framework\IO;

use Framework\IO\ImporterReader;
use Framework\IO\ImporterData;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Exception;
use Iterator;

/**
 * The Spreadsheet Reader
 */
class SpreadsheetReader implements ImporterReader, Iterator {

    private ?Worksheet $sheet;

    private int        $index   = 0;
    private int        $rows    = 0;
    private string     $columns = "";


    /**
     * Creates a new SpreadsheetReader instance
     * @param string $path
     */
    public function __construct(string $path) {
        try {
            $fileType    = IOFactory::identify($path);
            $reader      = IOFactory::createReader($fileType);
            $spreadsheet = $reader->load($path);
            $this->sheet = $spreadsheet->getSheet(0);
        } catch (Exception $e) {
            $this->sheet = null;
        }
    }

    /**
     * Returns true if the SpreadsheetReader is available
     * @return boolean
     */
    public static function isAvailable(): bool {
        return class_exists(Worksheet::class);
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
     * @param integer $amount
     * @return ImporterData
     */
    public function getData(int $amount = 3): ImporterData {
        $data = new ImporterData(
            columns: $this->getHeader(),
            amount:  1,
            first:   "",
            last:    "",
        );
        if (!$this->isValid()) {
            return $data;
        }

        $colAmount = $this->getHighestColumn();
        $rowAmount = $this->getHighestRow();
        $rows      = [
            "first" => $this->getRow(2, "A", $colAmount),
            "last"  => $this->getRow($rowAmount, "A", $colAmount),
        ];

        foreach ($rows as $index => $row) {
            if (empty($row)) {
                continue;
            }
            $fields = [];
            for ($i = 0; $i < $amount; $i++) {
                if (!empty($row[$i])) {
                    $fields[] = $row[$i];
                }
            }
            $data->$index = Strings::join($fields, " - ");
        }

        $data->amount = $rowAmount;
        return $data;
    }

    /**
     * Returns the Header
     * @return Select[]
     */
    public function getHeader(): array {
        $columns = [];
        if (!$this->isValid()) {
            return $columns;
        }

        $colAmount = $this->getHighestColumn();
        $headerRow = $this->getRow(1, "A", $colAmount);

        foreach ($headerRow as $key => $value) {
            $columns[] = new Select($key + 1, $value);
        }
        return $columns;
    }



    /**
     * Returns the Highest Sheet column, removing the empty ones
     * @return string
     */
    private function getHighestColumn(): string {
        if (empty($this->sheet)) {
            return "";
        }

        $colTotal  = $this->sheet->getHighestColumn();
        $colMin    = ord("A");
        $colAmount = ord($colTotal) - $colMin;
        $firstRow  = $this->getRow(1, "A", $colTotal);

        for ($i = $colAmount; $i >= 0; $i--) {
            if (!empty($firstRow[$i])) {
                break;
            }
            $colAmount -= 1;
        }
        return chr($colAmount + $colMin);
    }

    /**
     * Returns the Highest Sheet row, removing the empty ones
     * @return integer
     */
    private function getHighestRow(): int {
        if (empty($this->sheet)) {
            return 0;
        }

        $colAmount = $this->getHighestColumn();
        $rowAmount = $this->sheet->getHighestRow();

        for ($i = $rowAmount; $i > 0; $i--) {
            $row = $this->getRow($i, "A", $colAmount);
            $row = Arrays::removeEmpty($row);
            if (!empty($row)) {
                break;
            }
            $rowAmount -= 1;
        }
        return $rowAmount;
    }

    /**
     * Returns the Row from the current Sheet
     * @param integer $row
     * @param string  $from
     * @param string  $to
     * @return mixed[]
     */
    private function getRow(int $row, string $from, string $to): array {
        if (!$this->isValid() || (empty($from) && empty($to))) {
            return [];
        }

        try {
            $row = $this->sheet->rangeToArray(
                "{$from}{$row}:{$to}{$row}",
                nullValue: "",
                calculateFormulas: true,
                formatData: false,
            );
        } catch (Exception $e) {
            return [];
        }
        return $row[0];
    }



    /**
     * Starts the Iterator
     * @return void
     */
    public function rewind(): void {
        $this->index   = 2;
        $this->rows    = $this->getHighestRow();
        $this->columns = $this->getHighestColumn();
    }

    /**
     * Returns the current Row
     * @return mixed[]
     */
    public function current(): array {
        return $this->getRow($this->index, "A", $this->columns);
    }

    /**
     * Returns the current Key
     * @return integer
     */
    public function key(): int {
        return $this->index;
    }

    /**
     * Moves to the next Row
     * @return void
     */
    public function next(): void {
        $this->index++;
    }

    /**
     * Returns true if the current Row is valid
     * @return boolean
     */
    public function valid(): bool {
        return $this->index < $this->rows;
    }
}
