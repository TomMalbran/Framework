<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\IO\SpreadsheetSheet;
use Framework\Utils\Elements;
use Framework\Utils\Strings;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The Spreadsheet Writer
 */
class SpreadsheetWriter implements ExporterWriter {

    private string           $title;
    private string           $lang;
    private Spreadsheet      $data;
    private SpreadsheetSheet $sheet;
    private int              $sheetNum;

    /** @var SpreadsheetSheet[] */
    private array            $sheets;



    /**
     * Creates a new SpreadsheetWriter instance
     * @param string $title
     * @param string $lang  Optional.
     */
    public function __construct(string $title, string $lang = "root") {
        $this->title = NLS::get($title, $lang);
        $this->lang  = $lang;
        $this->data  = new Spreadsheet();
        $this->data->getProperties()->setTitle($this->title);

        $this->sheets   = [];
        $this->sheetNum = 0;
        $this->addSheet();
    }

    /**
     * Returns true if the SpreadsheetWriter is available
     * @param integer $total
     * @param integer $maxLines Optional.
     * @return boolean
     */
    public static function isAvailable(int $total, int $maxLines = 5000): bool {
        return class_exists(Spreadsheet::class) && $total < $maxLines;
    }



    /**
     * Adds a new Sheet
     * @param string              $sheetName Optional.
     * @param string|integer|null $sheetID   Optional.
     * @return SpreadsheetSheet
     */
    public function addSheet(string $sheetName = "", string|int|null $sheetID = null): SpreadsheetSheet {
        $count = $this->data->getSheetCount();
        if ($this->sheetNum < $count) {
            $sheet = $this->data->getSheet($this->sheetNum);
        } else {
            $sheet = $this->data->createSheet($this->sheetNum);
        }

        $sheet->setTitle(!empty($sheetName) ? NLS::get($sheetName, $this->lang) : $this->title);
        $spreadsheet = new SpreadsheetSheet($sheet, $this->lang);
        $sheetID     = $sheetID != null ? $sheetID : $this->sheetNum;

        $this->sheets[$sheetID] = $spreadsheet;
        $this->sheetNum += 1;
        $this->sheet     = $spreadsheet;
        return $spreadsheet;
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
     * Writes the Header
     * @param Elements $header
     * @return SpreadsheetWriter
     */
    public function writeHeader(Elements $header): SpreadsheetWriter {
        if ($this->sheet) {
            $this->sheet->setHeader($header);
        }
        return $this;
    }

    /**
     * Writes a Line
     * @param array{} $line
     * @return SpreadsheetWriter
     */
    public function writeLine(array $line): SpreadsheetWriter {
        if ($this->sheet) {
            $this->sheet->setLine($line);
        }
        return $this;
    }

    /**
     * Downloads the File
     * @param string $fileName
     * @return SpreadsheetWriter
     */
    public function downloadFile(string $fileName): SpreadsheetWriter {
        if ($this->sheet) {
            $this->sheet->autoSizeColumns();
        }
        $this->download($fileName, false);
        return $this;
    }



    /**
     * Creates a FileName with an optional date
     * @param string  $name
     * @param boolean $withDate
     * @return string
     */
    public function getFileName(string $name, bool $withDate = true): string {
        $result = NLS::get($name, $this->lang) . ($withDate ? "_" . date("Y-m-d") : "");
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
