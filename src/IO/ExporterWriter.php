<?php
namespace Framework\IO;

/**
 * The Exporter Writer
 */
interface ExporterWriter {

    /**
     * Writes the Header
     * @param array<string,string> $headers
     * @return ExporterWriter
     */
    public function writeHeader(array $headers): ExporterWriter;

    /**
     * Writes a Line
     * @param array{} $line
     * @return ExporterWriter
     */
    public function writeLine(array $line): ExporterWriter;

    /**
     * Downloads the file
     * @return ExporterWriter
     */
    public function downloadFile(): ExporterWriter;
}
