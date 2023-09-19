<?php
namespace Framework\IO;

use Framework\Request;
use Framework\NLS\NLS;
use Framework\IO\SpreadsheetWriter;
use Framework\IO\SpreadsheetSheet;
use Framework\Utils\Elements;
use Framework\Utils\Strings;

/**
 * The Exporter Wrapper
 */
class Exporter {

    private string            $fileName;
    private string            $lang;
    private int               $total;
    private Elements          $header;
    private bool              $isCSV;
    private SpreadsheetWriter $writer;
    private SpreadsheetSheet  $sheet;
    private mixed             $csv;

    private int $requests = 0;
    private int $perPage  = 2000;
    private int $page     = 0;
    private int $line     = 0;


    /**
     * Creates a new Spreadsheet instance
     * @param integer $total
     * @param string  $title
     * @param string  $fileName
     * @param integer $maxLines Optional.
     * @param string  $lang     Optional.
     */
    public function __construct(int $total, string $title, string $fileName, int $maxLines = 5000, string $lang = "root") {
        $this->fileName = $fileName;
        $this->lang     = $lang;
        $this->total    = $total;
        $this->header   = new Elements();

        if ($total < $maxLines) {
            $this->isCSV  = false;
            $this->writer = new SpreadsheetWriter($title, $lang);
            $this->sheet  = $this->writer->addSheet();
        } else {
            $this->isCSV  = true;
            $this->csv    = fopen("php://output", "w");
        }
        $this->start();
    }



    /**
     * Sets the Header
     * @param Elements $header
     * @return Exporter
     */
    public function setHeader(Elements $header): Exporter {
        $this->header = $header;
        $this->writeHeader();
        return $this;
    }

    /**
     * Adds multiple Headers
     * @param array{} $headers
     * @return Exporter
     */
    public function addHeaders(array $headers): Exporter {
        foreach ($headers as $key => $value) {
            $this->header->add($key, $value);
        }
        return $this;
    }

    /**
     * Adds a single Header
     * @param string  $key
     * @param string  $value
     * @param boolean $condition Optional.
     * @return Exporter
     */
    public function addHeader(string $key, string $value, bool $condition = true): Exporter {
        $this->header->add($key, $value, $condition);
        return $this;
    }

    /**
     * Removes multiple Headers
     * @param array{} $headers
     * @return Exporter
     */
    public function removeHeaders(array $headers): Exporter {
        foreach ($headers as $key) {
            $this->header->remove($key);
        }
        return $this;
    }

    /**
     * Writes the Header
     * @return Exporter
     */
    public function writeHeader(): Exporter {
        if ($this->isCSV) {
            $values = NLS::getAll($this->header->getValues(), $this->lang);
            fputcsv($this->csv, $values);
        } else {
            $this->sheet->setHeader($this->header);
        }
        return $this;
    }



    /**
     * Starts a Chunk Export
     * @param Request $request
     * @return Exporter
     */
    public function startChunk(Request $request): Exporter {
        $request->amount = $this->perPage;
        $request->page   = $this->page;
        $this->page     += 1;
        $this->requests += $this->perPage;
        return $this;
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
     * @param array{}[] $line
     * @return Exporter
     */
    public function writeLine(array $line): Exporter {
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
        return $this;
    }



    /**
     * Starts the Exporter
     * @return boolean
     */
    private function start(): bool {
        if (!$this->isCSV) {
            return false;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $fileName = NLS::get($this->fileName, $this->lang) . "_" . date("Y-m-d");
        header("Content-Type: application/csv");
        header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();
        return true;
    }

    /**
     * Downloads the File
     * @return boolean
     */
    public function download(): bool {
        if ($this->isCSV) {
            fclose($this->csv);
            flush();
            die();
        }

        $this->sheet->autoSizeColumns();
        $this->writer->download($this->fileName, true);
        return true;
    }
}
