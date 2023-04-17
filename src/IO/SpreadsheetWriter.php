<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\IO\SpreadsheetSheet;
use Framework\Utils\Strings;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The Spreadsheet Writer
 */
class SpreadsheetWriter {

    private string      $title;
    private Spreadsheet $data;
    private int         $sheetNum;

    /** @var SpreadsheetSheet[] */
    private array       $sheets;


    /**
     * Creates a new SpreadsheetWriter instance
     * @param string $title
     */
    public function __construct(string $title) {
        $this->title = NLS::get($title);
        $this->data  = new Spreadsheet();
        $this->data->getProperties()->setTitle($this->title);

        $this->sheets   = [];
        $this->sheetNum = 0;
    }



    /**
     * Adds a new Sheet
     * @param string              $sheetName Optional.
     * @param string|integer|null $sheetID   Optional.
     * @return SpreadsheetSheet
     */
    public function addSheet(string $sheetName = "", string|int $sheetID = null): SpreadsheetSheet {
        $count = $this->data->getSheetCount();
        if ($this->sheetNum < $count) {
            $sheet = $this->data->getSheet($this->sheetNum);
        } else {
            $sheet = $this->data->createSheet($this->sheetNum);
        }

        $sheet->setTitle(!empty($sheetName) ? NLS::get($sheetName) : $this->title);
        $ssheet  = new SpreadsheetSheet($sheet);
        $sheetID = $sheetID != null ? $sheetID : $this->sheetNum;

        $this->sheets[$sheetID] = $ssheet;
        $this->sheetNum += 1;
        return $ssheet;
    }

    /**
     * Returns the Sheet for the given ID, if possible
     * @param string|integer $sheetID
     * @return SpreadsheetSheet|null
     */
    public function getSheet(string|int $sheetID): ?SpreadsheetSheet {
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
    public function getFileName(string $name, bool $withDate = true): string {
        $result = NLS::get($name) . ($withDate ? "_" . date("Y-m-d") : "");
        return Strings::toLowerCase($result);
    }

    /**
     * Downloads the Spreadsheet file
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return SpreadsheetWriter
     */
    public function download(string $name, bool $withDate = true): SpreadsheetWriter {
        $fileName = $this->getFileName($name, $withDate);

        header("Content-type: application/vnd.ms-excel");
        header("Content-Encoding: none");
        header("Content-Disposition: attachment; filename=\"{$fileName}.xls\"");
        header("Cache-Control: private");
        header("Connection: close");

        $writer = IOFactory::createWriter($this->data, "Xls");
        $writer->save("php://output");
        return $this;
    }

    /**
     * Downloads the Spreadsheet file as xlsx
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return SpreadsheetWriter
     */
    public function downloadXlsx(string $name, bool $withDate = true): SpreadsheetWriter {
        $fileName = $this->getFileName($name, $withDate);

        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF16-LE; encoding=UTF16-LE");
        header("Content-Disposition: attachment; filename=\"{$fileName}.xlsx\"");
        header("Cache-Control: max-age=0");
        header("Connection: close");

        $writer = IOFactory::createWriter($this->data, "Xlsx");
        $writer->save("php://output");
        return $this;
    }

    /**
     * Downloads the Spreadsheet file as csv
     * @param string  $name
     * @param boolean $withDate Optional.
     * @return SpreadsheetWriter
     */
    public function downloadCsv(string $name, bool $withDate = true): SpreadsheetWriter {
        $fileName = $this->getFileName($name, $withDate);

        header("Content-Type: application/csv; charset=UTF16-LE; encoding=UTF16-LE");
        header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
        header("Cache-Control: private");
        header("Connection: close");

        $writer = IOFactory::createWriter($this->data, "Csv");
        $writer->save("php://output");
        return $this;
    }
}
