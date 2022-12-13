<?php
namespace Framework\IO;

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
     * Returns the Highet Sheet column, removing the empty ones
     * @return string
     */
    public function getHighestColumn(): string {
        if (empty($this->sheet)) {
            return "";
        }

        $colTotal = $this->sheet->getHighestColumn();
        $colMin   = ord("A");
        $colAmt   = ord($colTotal) - $colMin;
        $firstRow = $this->getRow(1, "A", $colTotal);

        for ($i = $colAmt; $i >= 0; $i--) {
            if (!empty($firstRow[$i])) {
                break;
            }
            $colAmt--;
        }
        return chr($colAmt + $colMin);
    }

    /**
     * Returns the Highet Sheet row, removing the empty ones
     * @return integer
     */
    public function getHighestRow(): int {
        if (empty($this->sheet)) {
            return 0;
        }

        $colAmt = $this->getHighestColumn();
        $rowAmt = $this->sheet->getHighestRow();

        for ($i = $rowAmt; $i >= 0; $i--) {
            $row = $this->getRow($i, "A", $colAmt);
            if (!empty($row)) {
                break;
            }
            $rowAmt--;
        }
        return $rowAmt;
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
}
