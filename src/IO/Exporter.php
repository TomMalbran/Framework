<?php
namespace Framework\IO;

use Framework\Request;
use Framework\Core\NLS;
use Framework\IO\ExporterWriter;
use Framework\IO\XLSXWriter;
use Framework\IO\CSVWriter;

/**
 * The Exporter Wrapper
 */
class Exporter {

    private string         $fileName;
    private int            $total;

    /** @var array<string,string> */
    private array          $headers;

    private ExporterWriter $writer;

    private int $requests = 0;
    private int $page     = 0;
    private int $line     = 0;


    /**
     * Creates a new Exporter instance
     * @param integer $total
     * @param string  $title
     * @param string  $fileName
     * @param string  $lang     Optional.
     */
    public function __construct(int $total, string $title, string $fileName, string $lang = "root") {
        $this->fileName = NLS::getString($fileName, $lang) . "_" . date("Y-m-d");
        $this->total    = $total;
        $this->headers  = [];

        if (XLSXWriter::isAvailable()) {
            $this->writer = new XLSXWriter($title, $this->fileName, $lang);
        } else {
            $this->writer = new CSVWriter($this->fileName, $lang);
        }
    }



    /**
     * Adds multiple Headers
     * @param array<string,string> $headers
     * @return Exporter
     */
    public function addHeaders(array $headers): Exporter {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
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
        if ($condition) {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * Removes multiple Headers
     * @param string[] $headers
     * @return Exporter
     */
    public function removeHeaders(array $headers): Exporter {
        foreach ($headers as $key) {
            unset($this->headers[$key]);
        }
        return $this;
    }

    /**
     * Writes the Header
     * @return Exporter
     */
    public function writeHeader(): Exporter {
        $this->writer->writeHeader($this->headers);
        return $this;
    }



    /**
     * Starts a Chunk to Export
     * @param Request $request
     * @param integer $perPage Optional.
     * @return Exporter
     */
    public function startChunk(Request $request, int $perPage = 2000): Exporter {
        $request->set("amount", $perPage);
        $request->set("page", $this->page);

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
     * @param array<string,string|integer|float> $line
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
        $this->writer->downloadFile();
        exit();
    }
}
