<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\IO\ExporterWriter;
use Framework\Utils\Elements;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

/**
 * The XLSX Writer
 */
class XLSXWriter implements ExporterWriter {

    private string   $title;
    private string   $lang;
    private Elements $header;
    private Writer   $writer;


    /**
     * Creates a new XLSXWriter instance
     * @param string $title
     * @param string $fileName
     * @param string $lang     Optional.
     */
    public function __construct(string $title, string $fileName, string $lang = "root") {
        $this->title = NLS::get($title, $lang);
        $this->lang  = $lang;

        $this->writer = new Writer();
        $this->writer->openToBrowser($fileName);

        $sheet = $this->writer->getCurrentSheet();
        $sheet->setName($this->title);
    }



    /**
     * Writes the Header
     * @param Elements $header
     * @return XLSXWriter
     */
    public function writeHeader(Elements $header): XLSXWriter {
        $this->header = $header;

        $values = NLS::getAll($header->getValues(), $this->lang);
        $row    = Row::fromValues($values);
        $this->writer->addRow($row);
        return $this;
    }

    /**
     * Writes a Line
     * @param array{} $line
     * @return XLSXWriter
     */
    public function writeLine(array $line): XLSXWriter {
        $values = $this->header->parseValues($line);
        $row    = Row::fromValues($values);
        $this->writer->addRow($row);
        return $this;
    }

    /**
     * Downloads the File
     * @param string $fileName
     * @return XLSXWriter
     */
    public function downloadFile(string $fileName): XLSXWriter {
        $this->writer->close();
        return $this;
    }
}
