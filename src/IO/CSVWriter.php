<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\IO\ExporterWriter;
use Framework\Utils\Elements;

/**
 * The CSV Writer
 */
class CSVWriter implements ExporterWriter {

    private string   $fileName;
    private string   $lang;
    private Elements $header;
    private mixed    $file;
    private int      $line = 0;


    /**
     * Creates a new CSVWriter instance
     * @param string $fileName
     * @param string $lang     Optional.
     */
    public function __construct(string $fileName, string $lang = "root") {
        $this->fileName = $fileName;
        $this->lang     = $lang;
        $this->file     = fopen("php://output", "w");

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
     * @param Elements $header
     * @return CSVWriter
     */
    public function writeHeader(Elements $header): CSVWriter {
        $this->header = $header;
        $values = NLS::getAll($header->getValues(), $this->lang);
        fputcsv($this->file, $values);
        return $this;
    }

    /**
     * Writes a Line
     * @param array{} $line
     * @return CSVWriter
     */
    public function writeLine(array $line): CSVWriter {
        $this->line += 1;
        $values = $this->header->parseValues($line);
        fputcsv($this->file, $values);
        if ($this->line % 100 == 0) {
            flush();
        }
        return $this;
    }

    /**
     * Downloads the File
     * @param string $fileName
     * @return CSVWriter
     */
    public function downloadFile(string $fileName): CSVWriter {
        fclose($this->file);
        flush();
        return $this;
    }
}
