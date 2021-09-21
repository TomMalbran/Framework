<?php
namespace Framework\IO;

use Framework\Request;
use Framework\NLS\NLS;
use Framework\IO\SpreadsheetWriter;
use Framework\Utils\Elements;

/**
 * The Exporter Wrapper
 */
class Exporter {

    private $isCSV;
    private $writer;
    private $sheet;
    private $csv;

    private $fileName = "";
    private $total    = 0;
    private $header   = null;
    private $requests = 0;
    private $perPage  = 2000;
    private $page     = 0;
    private $line     = 0;


    /**
     * Creates a new Spreadsheet instance
     * @param integer $total
     * @param string  $title
     * @param string  $fileName
     */
    public function __construct(int $total, string $title, string $fileName) {
        $this->fileName = $fileName;
        $this->total    = $total;
        $this->header   = new Elements();

        if ($total < 6000) {
            $this->isCSV  = false;
            $this->writer = new SpreadsheetWriter($title);
            $this->sheet  = $this->writer->addSheet();
        } else {
            $this->isCSV  = true;
            $this->csv    = fopen("php://output", "w");

            if (ob_get_level()) {
                ob_end_clean();
            }
            $fileName = NLS::get($fileName);
            header("Content-Type: application/csv");
            header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            flush();
        }
    }



    /**
     * Sets the Header
     * @param Elements $header
     * @return void
     */
    public function setHeader(Elements $header): void {
        $this->header = $header;
        $this->writeHeader();
    }

    /**
     * Adds multiple Headers
     * @param array $headers
     * @return void
     */
    public function addHeaders(array $headers): void {
        foreach ($headers as $key => $value) {
            $this->header->add($key, $value);
        }
    }

    /**
     * Adds a simgle Header
     * @param string  $key
     * @param string  $value
     * @param boolean $condition Optional.
     * @return void
     */
    public function addHeader(string $key, string $value, bool $condition = true): void {
        $this->header->add($key, $value, $condition);
    }

    /**
     * Writes the Header
     * @return void
     */
    public function writeHeader(): void {
        if ($this->isCSV) {
            $values = NLS::getAll($this->header->getValues());
            fputcsv($this->csv, $values);
        } else {
            $this->sheet->setHeader($this->header);
        }
    }



    /**
     * Starts a Chumk Export
     * @param Request $request
     * @return void
     */
    public function startChunk(Request $request): void {
        $request->amount = $this->perPage;
        $request->page   = $this->page;
        $this->page     += 1;
        $this->requests += $this->perPage;
    }

    /**
     * Returns true ig the report is complete
     * @return boolean
     */
    public function isComplete(): bool {
        return $this->requests >= $this->total || $this->line >= $this->total;
    }

    /**
     * Writes a Line
     * @param array $line
     * @return void
     */
    public function writeLine(array $line): void {
        $this->line += 1;

        if ($this->isCSV) {
            $parsed = $this->header->parseValues($line);
            fputcsv($this->csv, $parsed);
            if ($this->line % 100 == 0) {
                flush();
            }
        } else {
            $this->sheet->setLine($line);
        }
    }



    /**
     * Downloads the File
     * @return void
     */
    public function download(): void {
        if ($this->isCSV) {
            fclose($this->csv);
            flush();
            die();
        } else {
            $this->sheet->autoSizeColumns();
            $this->writer->download($this->fileName, true);
        }
    }
}
