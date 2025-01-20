<?php
namespace Framework\IO;

use Framework\Request;
use Framework\NLS\NLS;
use Framework\IO\ExporterWriter;
use Framework\IO\XLSXWriter;
use Framework\IO\SpreadsheetWriter;
use Framework\IO\CSVWriter;
use Framework\Utils\Elements;

/**
 * The Exporter Wrapper
 */
class Exporter {

    private string         $fileName;
    private int            $total;
    private Elements       $header;
    private ExporterWriter $writer;

    private int $requests = 0;
    private int $page     = 0;
    private int $line     = 0;


    /**
     * Creates a new Exporter instance
     * @param integer $total
     * @param string  $title
     * @param string  $fileName
     * @param integer $maxLines Optional.
     * @param string  $lang     Optional.
     */
    public function __construct(int $total, string $title, string $fileName, int $maxLines = 5000, string $lang = "root") {
        $this->fileName = NLS::get($fileName, $lang) . "_" . date("Y-m-d");
        $this->total    = $total;
        $this->header   = new Elements();

        if (XLSXWriter::isAvailable()) {
            $this->writer = new XLSXWriter($title, $this->fileName, $lang);
        } elseif (SpreadsheetWriter::isAvailable($total, $maxLines)) {
            $this->writer = new SpreadsheetWriter($title, $lang);
        } else {
            $this->writer = new CSVWriter($this->fileName, $lang);
        }
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
        $this->writer->writeHeader($this->header);
        return $this;
    }



    /**
     * Starts a Chunk to Export
     * @param Request $request
     * @param integer $perPage Optional.
     * @return Exporter
     */
    public function startChunk(Request $request, int $perPage = 2000): Exporter {
        $request->amount = $perPage;
        $request->page   = $this->page;
        $this->page     += 1;
        $this->requests += $perPage;
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
        $this->writer->writeLine($line);
        return $this;
    }

    /**
     * Downloads the File
     * @return never
     */
    public function download(): never {
        $this->writer->downloadFile($this->fileName);
        exit();
    }
}
