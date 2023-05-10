<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\Utils\Elements;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\WorkSheet\WorkSheet;

/**
 * The Spreadsheet Sheet
 */
class SpreadsheetSheet {

    private WorkSheet $sheet;
    private string    $lang;
    private int       $row;
    private ?Elements $header;


    /**
     * Creates a new SpreadsheetSheet instance
     * @param WorkSheet $sheet
     * @param string    $lang  Optional.
     */
    public function __construct(WorkSheet $sheet, string $lang = "root") {
        $this->sheet  = $sheet;
        $this->lang   = $lang;
        $this->row    = 1;
        $this->header = null;
    }



    /**
     * Sets the Header
     * @param Elements $header
     * @return SpreadsheetSheet
     */
    public function setHeader(Elements $header): SpreadsheetSheet {
        $this->header = $header;
        $values = $header->getValues();
        $header = NLS::getAll($values, $this->lang);
        $this->writeHeader($header);
        return $this;
    }

    /**
     * Sets a Line
     * @param array{} $line
     * @return SpreadsheetSheet
     */
    public function setLine(array $line): SpreadsheetSheet {
        $parsed = $this->header->parseValues($line);
        $this->writeLine($parsed);
        return $this;
    }



    /**
     * Adds the Header
     * @param string ...$values
     * @return SpreadsheetSheet
     */
    public function addHeader(string ...$values): SpreadsheetSheet {
        $header = NLS::getAll($values, $this->lang);
        $this->writeHeader($header);
        return $this;
    }

    /**
     * Adds a Line
     * @param string ...$line
     * @return SpreadsheetSheet
     */
    public function addLine(string ...$line): SpreadsheetSheet {
        $this->writeLine($line);
        return $this;
    }



    /**
     * Writes the content in a new line and formats it
     * @param array{} $content
     * @param boolean $makeBold      Optional.
     * @param boolean $formatNumbers Optional.
     * @return SpreadsheetSheet
     */
    public function writeLine(array $content, bool $makeBold = false, bool $formatNumbers = false): SpreadsheetSheet {
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
     * @param array{} $content
     * @return SpreadsheetSheet
     */
    public function writeHeader(array $content): SpreadsheetSheet {
        $this->writeLine($content, true, false);
        return $this;
    }

    /**
     * Writes the content and formats the numbers
     * @param array{} $content
     * @return SpreadsheetSheet
     */
    public function writeNumbers(array $content): SpreadsheetSheet {
        $this->writeLine($content, false, true);
        return $this;
    }

    /**
     * Increases the row count by the given amount
     * @param integer $amount Optional.
     * @return SpreadsheetSheet
     */
    public function addBlankLine(int $amount = 1): SpreadsheetSheet {
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
    public function makeBold(string $fromCol, string $toCol, int $rowCount): SpreadsheetSheet {
        $range = "$fromCol$rowCount:$toCol$rowCount";
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
    public function formatNumbers(string $fromCol, string $toCol, int $rowCount): SpreadsheetSheet {
        $range = "$fromCol$rowCount:$toCol$rowCount";
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode("0.00");
        return $this;
    }



    /**
     * Auto sizes the columns
     * @return SpreadsheetSheet
     */
    public function autoSizeColumns(): SpreadsheetSheet {
        $cellIterator = $this->sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $this->sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }
        return $this;
    }
}
