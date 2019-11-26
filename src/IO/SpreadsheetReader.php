<?php
namespace Framework\IO;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

/**
 * The Spreadsheet Reader
 */
class SpreadsheetReader {

    private $sheet;

    
    /**
     * Creates a new SpreadsheetReader instance
     * @param string $path
     */
    public function __construct($path) {
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
    public function isValid() {
        return $this->sheet !== null;
    }
    


    /**
     * Returns the Highet Sheet column, removing the empty ones
     * @return string
     */
    public function getHighestColumn() {
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
    public function getHighestRow() {
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
     * Returns the Row from the given Sheet
     * @param integer $row
     * @param string  $from
     * @param string  $to
     * @return array
     */
    public function getRow($row, $from, $to) {
        try {
            $row = $this->sheet->rangeToArray("{$from}{$row}:{$to}{$row}", null, true, false);
        } catch (Exception $e) {
            return [];
        }
        return $row[0];
    }
}
