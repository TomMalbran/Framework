<?php
namespace Framework\IO;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\WorkSheet\WorkSheet;

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
     * @return void
     */
    public function writeLine(array $content, bool $makeBold = false, bool $formatNumbers = false): void {
        $this->sheet->fromArray($content, null, "A" . $this->row, true);
        $toCol = Coordinate::stringFromColumnIndex(count($content));

        if ($makeBold) {
            $this->makeBold("a", $toCol, $this->row);
        }
        if ($formatNumbers) {
            $this->formatNumbers("a", $toCol, $this->row);
        }
        $this->row += 1;
    }

    /**
     * Writes the content in bold
     * @param array $content
     * @return void
     */
    public function writeHeader(array $content): void {
        $this->writeLine($content, true, false);
    }

    /**
     * Writes the content and formats the numbers
     * @param array $content
     * @return void
     */
    public function writeNumbers(array $content): void {
        $this->writeLine($content, false, true);
    }

    /**
     * Increases the row count by the given amount
     * @param integer $amount Optional.
     * @return void
     */
    public function addBlankLine(int $amount = 1): void {
        $this->row += $amount;
    }



    /**
     * Makes a range bold
     * @param string  $fromCol
     * @param string  $toCol
     * @param integer $rowCount
     * @return void
     */
    public function makeBold(string $fromCol, string $toCol, int $rowCount): void {
        $range = "$fromCol$rowCount:$toCol$rowCount";
        $this->sheet->getStyle($range)->applyFromArray([ "font" => [ "bold" => true ]]);
    }

    /**
     * Makes the numbers have always 2 decimals
     * @param string  $fromCol
     * @param string  $toCol
     * @param integer $rowCount
     * @return void
     */
    public function formatNumbers(string $fromCol, string $toCol, int $rowCount): void {
        $range = "$fromCol$rowCount:$toCol$rowCount";
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode("0.00");
    }



    /**
     * Auto sizes the columns
     * @return void
     */
    public function autoSizeColumns(): void {
        $cellIterator = $this->sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $this->sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }
    }
}
