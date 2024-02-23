<?php
namespace Framework\IO;

use Framework\Utils\Elements;

/**
 * The Exporter Writer
 */
interface ExporterWriter
{

    /**
     * Writes the Header
     * @param Elements $header
     * @return ExporterWriter
     */
    public function writeHeader(Elements $header): ExporterWriter;

    /**
     * Writes a Line
     * @param array{} $line
     * @return ExporterWriter
     */
    public function writeLine(array $line): ExporterWriter;

    /**
     * Downloads the file
     * @param string $fileName
     * @return ExporterWriter
     */
    public function downloadFile(string $fileName): ExporterWriter;
}
