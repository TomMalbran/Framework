<?php
namespace Framework\IO;

use Framework\Intl\NLS;
use Framework\IO\ExporterWriter;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * The XLSX Writer
 */
class XLSXWriter implements ExporterWriter {

    /** @var array<string,string> */
    private array  $headers = [];
    private string $title;
    private string $lang;
    private Writer $writer;


    /**
     * Creates a new XLSXWriter instance
     * @param string $title
     * @param string $fileName
     * @param string $lang     Optional.
     */
    public function __construct(string $title, string $fileName, string $lang = "root") {
        $this->title = NLS::getString($title, $lang);
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
     * @param array<string,string> $headers
     * @return XLSXWriter
     */
    #[\Override]
    public function writeHeader(array $headers): XLSXWriter {
        $this->headers = $headers;

        $values = NLS::getAll(array_values($headers), $this->lang);
        $row    = Row::fromValues(array_values($values));
        $this->writer->addRow($row);
        return $this;
    }

    /**
     * Writes a Line
     * @param array<string,string> $line
     * @return XLSXWriter
     */
    #[\Override]
    public function writeLine(array $line): XLSXWriter {
        $values = [];
        foreach (array_keys($this->headers) as $key) {
            $values[] = $line[$key] ?? "";
        }

        $row = Row::fromValues($values);
        $this->writer->addRow($row);
        return $this;
    }

    /**
     * Downloads the File
     * @return XLSXWriter
     */
    #[\Override]
    public function downloadFile(): XLSXWriter {
        $this->writer->close();
        return $this;
    }
}
