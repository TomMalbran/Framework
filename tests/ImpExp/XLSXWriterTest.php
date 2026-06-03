<?php
namespace Tests\ImpExp;

use Framework\ImpExp\XLSXWriter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ZipArchive;

class XLSXWriterTest extends TestCase {

    private string $path = "";


    protected function setUp(): void {
        $path = tempnam(sys_get_temp_dir(), "xlsx_writer_test_");
        $this->assertNotFalse($path);
        $this->path = $path . ".xlsx";
        $this->assertTrue(unlink($path));
    }

    protected function tearDown(): void {
        if ($this->path !== "" && is_file($this->path)) {
            @unlink($this->path);
        }
    }


    public function testIsAvailable(): void {
        $this->assertTrue(XLSXWriter::isAvailable());
    }

    public function testWriteHeader(): void {
        $writer = new XLSXWriter("Export", "export", "root", $this->path);
        $result = $writer->writeHeader([
            "name" => "Name",
            "age"  => "Age",
        ])->downloadFile();

        $sheet = $this->getSheetXml();

        $this->assertSame($writer, $result);
        $this->assertStringContainsString('<dimension ref="A1:B1"/>', $sheet);
        $this->assertStringContainsString('<c r="A1" s="0" t="inlineStr"><is><t>Name</t></is></c>', $sheet);
        $this->assertStringContainsString('<c r="B1" s="0" t="inlineStr"><is><t>Age</t></is></c>', $sheet);
    }

    public function testWriteLine(): void {
        $writer = new XLSXWriter("Export", "export", "root", $this->path);
        $result = $writer->writeHeader([
            "name" => "Name",
            "age"  => "Age",
            "city" => "City",
        ])->writeLine([
            "name" => "Alice",
            "age"  => 30,
            "city" => "Paris",
        ])->writeLine([
            "name" => "Bob",
            "age"  => 0,
        ])->downloadFile();

        $sheet = $this->getSheetXml();

        $this->assertSame($writer, $result);
        $this->assertStringContainsString('<dimension ref="A1:C3"/>', $sheet);
        $this->assertStringContainsString('<row r="2" spans="1:3" customHeight="0">', $sheet);
        $this->assertStringContainsString('<c r="A2" s="0" t="inlineStr"><is><t>Alice</t></is></c>', $sheet);
        $this->assertStringContainsString('<c r="B2" s="0"><v>30</v></c>', $sheet);
        $this->assertStringContainsString('<c r="C2" s="0" t="inlineStr"><is><t>Paris</t></is></c>', $sheet);
        $this->assertStringContainsString('<c r="A3" s="0" t="inlineStr"><is><t>Bob</t></is></c>', $sheet);
        $this->assertStringContainsString('<c r="B3" s="0"><v>0</v></c>', $sheet);
        $this->assertStringNotContainsString('r="C3"', $sheet);
    }

    public function testDownloadFile(): void {
        $writer = new XLSXWriter("Export", "export", "root", $this->path);
        $result = $writer->downloadFile();

        $this->assertSame($writer, $result);
        $this->assertTrue(is_file($this->path));
        $this->assertGreaterThan(0, filesize($this->path));
    }

    public function testConstructorSetsSheetName(): void {
        (new XLSXWriter("Export", "export", "root", $this->path))->downloadFile();

        $workbook = $this->getZipContent("xl/workbook.xml");

        $this->assertStringContainsString('<sheet name="Export" sheetId="1" r:id="rIdSheet1" state="visible"/>', $workbook);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstructorStartsBrowserDownloadOutput(): void {
        $writer = new XLSXWriter("Export", "export");
        $writer->writeHeader([ "name" => "Name" ]);
        $writer->downloadFile();

        $this->expectOutputRegex('/^PK/');
    }


    private function getSheetXml(): string {
        return $this->getZipContent("xl/worksheets/sheet1.xml");
    }

    private function getZipContent(string $name): string {
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($this->path));

        $content = $zip->getFromName($name);
        $zip->close();

        $this->assertNotFalse($content);
        return $content;
    }
}
