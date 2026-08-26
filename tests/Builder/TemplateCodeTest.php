<?php
namespace Tests\Builder;

use Framework\Application;
use Framework\Builder\TemplateCode;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TemplateCodeTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Builder/.tmp_template_code";

    private string $fixtureBase = "";


    protected function setUp(): void {
        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
    }

    protected function tearDown(): void {
        Storage::deleteDir($this->fixtureBase);
    }

    private function writeTemplate(string $relPath): void {
        $filePath = $this->fixtureBase . "/" . $relPath;
        Storage::createDir(Storage::getDirectory($filePath));
        Storage::writeFile($filePath, "{{content}}");
    }


    #[DataProvider("providerCollectTemplates")]
    public function testCollectTemplates(array $files, array $expected): void {
        foreach ($files as $file) {
            $this->writeTemplate($file);
        }

        $result = TemplateCode::collectTemplates($this->fixtureBase);
        $this->assertSame($expected, $result["templates"]);
        $this->assertSame(count($expected), $result["total"]);
    }

    public static function providerCollectTemplates(): array {
        return [
            "empty input"                 => [
                [],
                [],
            ],
            "skips other extensions"      => [
                [ "readme.txt", "data.json" ],
                [],
            ],
            "single template"             => [
                [ "one.mu" ],
                [
                    [ "name" => "one", "relPath" => "/one.mu", "constant" => "one" ],
                ],
            ],
            "nested templates are padded" => [
                [ "alpha.mu", "sub/b.mu", "other.txt" ],
                [
                    [ "name" => "alpha", "relPath" => "/alpha.mu",  "constant" => "alpha" ],
                    [ "name" => "b",     "relPath" => "/sub/b.mu",  "constant" => "b    " ],
                ],
            ],
            "skips vendor dir"            => [
                [ "kept.mu", "vendor/skipped.mu" ],
                [
                    [ "name" => "kept", "relPath" => "/kept.mu", "constant" => "kept" ],
                ],
            ],
        ];
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, TemplateCode::destroyCode());
    }
}
