<?php
namespace Framework\IO;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Exception;

/**
 * The Spreadsheet Reader
 */
class SpreadsheetReader {

    private ?Worksheet $sheet;
    private ?string    $columns;


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
     * Returns true if the Sheet is valid
     * @return boolean
     */
    public function isValid(): bool {
        return $this->sheet !== null;
    }



    /**
     * Returns the Highest Sheet column, removing the empty ones
     * @return string
     */
    public function getHighestColumn(): string {
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
    public function getHighestRow(): int {
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
     * @param string  $from Optional.
     * @param string  $to   Optional.
     * @return mixed[]
     */
    public function getRow(int $row, string $from = "A", string $to = ""): array {
        if (empty($this->sheet)) {
            return [];
        }

        if (empty($to)) {
            if (empty($this->columns)) {
                $this->columns = $this->getHighestColumn();
            }
            $to = $this->columns;
        }
        if (empty($from) || empty($to)) {
            return [];
        }

        try {
            $row = $this->sheet->rangeToArray("{$from}{$row}:{$to}{$row}", null, true, false);
        } catch (Exception $e) {
            return [];
        }
        return $row[0];
    }

    /**
     * Returns the Header
     * @return array{}[]
     */
    public function getHeader(): array {
        $columns = [];
        if (!$this->isValid()) {
            return $columns;
        }

        $colAmount = $this->getHighestColumn();
        $headerRow = $this->getRow(1, "A", $colAmount);

        foreach ($headerRow as $key => $value) {
            $columns[] = [
                "key"   => $key,
                "value" => $value,
            ];
        }
        return $columns;
    }

    /**
     * Returns the first and last values
     * @param integer $amount
     * @return array{}
     */
    public function getFirstAndLast(int $amount = 3): array {
        $values = [
            "first" => "",
            "last"  => "",
        ];
        if (!$this->isValid()) {
            return $values;
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
            $values[$index] = Strings::join($fields, " - ");
        }
        return $values;
    }
}
