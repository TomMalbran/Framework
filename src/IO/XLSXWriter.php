<?php
namespace Framework\IO;

use Framework\NLS\NLS;
use Framework\IO\ExporterWriter;
use Framework\Utils\Elements;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

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

        $options = new Options();
        $options->DEFAULT_ROW_STYLE = new Style();

        $this->writer = new Writer($options);
        $this->writer->openToBrowser("$fileName.xlsx");

        $sheet = $this->writer->getCurrentSheet();
        $sheet->setName($this->title);
    }

    /**
     * Returns true if the XLSXWriter is available
     * @return boolean
     */
    public static function isAvailable(): bool {
        return class_exists(Writer::class);
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
