<?php
namespace Framework\IO;

use Framework\IO\SpreadsheetSheet;
use Framework\Utils\Strings;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The Spreadsheet Writer
 */
class SpreadsheetWriter {
    
    private $data;
    private $sheets;
    private $sheetNum;
    
    
    /**
     * Creates a new SpreadsheetWriter instance
     * @param string $title
     * @param string $creator Optional.
     */
    public function __construct($title, $creator = "") {
        $this->data = new Spreadsheet();
        $this->data->getProperties()->setCreator($creator)->setTitle($title);
        
        $this->sheets   = [];
        $this->sheetNum = 0;
    }



    /**
     * Adds a new Sheet
     * @param string         $sheetName
     * @param string|integer $sheetID   Optional.
     * @return SpreadsheetSheet
     */
    public function addSheet($sheetName, $sheetID = null) {
        $count = $this->data->getSheetCount();
        if ($this->sheetNum < $count) {
            $sheet = $this->data->getSheet($this->sheetNum);
        } else {
            $sheet = $this->data->createSheet($this->sheetNum);
        }
        
        $sheet->setTitle($sheetName);
        $ssheet  = new SpreadsheetSheet($sheet);
        $sheetID = $sheetID != null ? $sheetID : $this->sheetNum;

        $this->sheets[$sheetID] = $ssheet;
        $this->sheetNum += 1;
        return $ssheet;
    }

    /**
     * Returns the Sheet for the given ID, if possible
     * @param string|integer $sheetID
     * @return SpreadsheetSheet
     */
    public function getSheet($sheetID) {
        if (!empty($this->sheets[$sheetID])) {
            return $this->sheets[$sheetID];
        }
        return null;
    }
    
    
    
    /**
     * Creates a FileName with an optional date
     * @param string  $name
     * @param boolean $withDate
     * @return string
     */
    public function getFileName($name, $withDate = true) {
        $result = $name . ($withDate ? "_" . date("dMY") : "");
        return Strings::toLowerCase($result);
    }

    /**
     * Downloads the Spreadsheet file
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return void
     */
    public function download($name, $withDate = true) {
        $fileName = $this->getFileName($name, $withDate);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Encoding: none");
        header("Content-Disposition: attachment; filename=\"{$fileName}.xls\"");
        header("Cache-Control: private");
        header("Connection: close");
        
        $writer = IOFactory::createWriter($this->data, "Xls");
        $writer->save("php://output");
    }

    /**
     * Downloads the Spreadsheet file as xlsx
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return void
     */
    public function downloadXlsx($name, $withDate = true) {
        $fileName = $this->getFileName($name, $withDate);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF16-LE; encoding=UTF16-LE");
        header("Content-Disposition: attachment; filename=\"{$fileName}.xlsx\"");
        header("Cache-Control: max-age=0");
        header("Connection: close");
        
        $writer = IOFactory::createWriter($this->data, "Xlsx");
        $writer->save("php://output");
    }

    /**
     * Downloads the Spreadsheet file as csv
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return void
     */
    public function downloadCsv($name, $withDate = true) {
        $fileName = $this->getFileName($name, $withDate);
        header("Content-Type: application/csv; charset=UTF16-LE; encoding=UTF16-LE");
        header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
        header("Cache-Control: private");
        header("Connection: close");
        
        $writer = IOFactory::createWriter($this->data, "Csv");
        $writer->save("php://output");
    }
}
