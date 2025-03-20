<?php
namespace Framework\IO;

use Framework\Core\NLS;
use Framework\IO\ExporterWriter;

/**
 * The CSV Writer
 */
class CSVWriter implements ExporterWriter {

    /** @var array<string,string> */
    private array  $headers;
    private string $fileName;
    private string $lang;

    /** @var resource */
    private mixed  $file;
    private int    $line = 0;



    /**
     * Creates a new CSVWriter instance
     * @param string $fileName
     * @param string $lang     Optional.
     */
    public function __construct(string $fileName, string $lang = "root") {
        $this->fileName = $fileName;
        $this->lang     = $lang;

        $handle = fopen("php://output", "w");
        if ($handle !== false) {
            $this->file = $handle;
        }

        $this->start();
    }

    /**
     * Starts the Writer
     * @return boolean
     */
    private function start(): bool {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: application/csv");
        header("Content-Disposition: attachment; filename=\"{$this->fileName}.csv\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();
        return true;
    }



    /**
     * Writes the Header
     * @param array<string,string> $headers
     * @return CSVWriter
     */
    public function writeHeader(array $headers): CSVWriter {
        $this->headers = $headers;
        $values = NLS::getAll(array_values($headers), $this->lang);
        fputcsv($this->file, $values);
        return $this;
    }

    /**
     * Writes a Line
     * @param array<string,string> $line
     * @return CSVWriter
     */
    public function writeLine(array $line): CSVWriter {
        $values = [];
        foreach (array_keys($this->headers) as $key) {
            $values[] = $line[$key] ?? "";
        }

        $this->line += 1;
        fputcsv($this->file, $values);
        if ($this->line % 100 == 0) {
            flush();
        }
        return $this;
    }

    /**
     * Downloads the File
     * @return CSVWriter
     */
    public function downloadFile(): CSVWriter {
        fclose($this->file);
        flush();
        return $this;
    }
}
