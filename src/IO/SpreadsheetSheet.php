<?php
namespace Framework\IO;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\WorkSheet;

/**
 * The Spreadsheet Sheet
 */
class SpreadsheetSheet {
    
    private $sheet;
    private $row;
    
    
    /**
     * Creates a new SpreadsheetSheet instance
     * @param WorkSheet $sheet
     */
    public function __construct(WorkSheet $sheet) {
        $this->sheet = $sheet;
        $this->row   = 1;
    }
    
    
    
    /**
     * Writes the content in a new line and formats it
     * @param array   $content
     * @param boolean $makeBold      Optional.
     * @param boolean $formatNumbers Optional.
     * @return SpreadsheetSheet
     */
    public function writeLine(array $content, $makeBold = false, $formatNumbers = false) {
        $this->sheet->fromArray($content, null, "A" . $this->row, true);
        $toCol = Coordinate::stringFromColumnIndex(count($content));
        
        if ($makeBold) {
            $this->makeBold("a", $toCol, $this->row);
        }
        if ($formatNumbers) {
            $this->formatNumbers("a", $toCol, $this->row);
        }
        $this->row += 1;
        return $this;
    }
    
    /**
     * Writes the content in bold
     * @param array $content
     * @return SpreadsheetSheet
     */
    public function writeHeader(array $content) {
        return $this->writeLine($content, true, false);
    }
    
    /**
     * Writes the content and formats the numbers
     * @param array $content
     * @return SpreadsheetSheet
     */
    public function writeNumbers(array $content) {
        return $this->writeLine($content, false, true);
    }
    
    /**
     * Increases the row count by the given amount
     * @param integer $amount Optional.
     * @return SpreadsheetSheet
     */
    public function addBlankLine($amount = 1) {
        $this->row += $amount;
        return $this;
    }
    
    
    
    /**
     * Makes a range bold
     * @param string  $fromCol
     * @param string  $toCol
     * @param integer $rowCount
     * @return SpreadsheetSheet
     */
    public function makeBold($fromCol, $toCol, $rowCount) {
        $range = $fromCol . $rowCount . ":" . $toCol . $rowCount;
        $this->sheet->getStyle($range)->applyFromArray([ "font" => [ "bold" => true ]]);
        return $this;
    }
    
    /**
     * Makes the numbers have always 2 decimals
     * @param string  $fromCol
     * @param string  $toCol
     * @param integer $rowCount
     * @return SpreadsheetSheet
     */
    public function formatNumbers($fromCol, $toCol, $rowCount) {
        $range = $fromCol . $rowCount . ":" . $toCol . $rowCount;
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode("0.00");
        return $this;
    }
    
    
    
    /**
     * Auto sizes the columns
     * @return SpreadsheetSheet
     */
    public function autoSizeColumns() {
        $cellIterator = $this->sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $this->sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }
        return $this;
    }
}
