<?php
namespace Tests\File;

use Framework\File\FileList;
use Framework\File\Type\FileItem;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FileListTest extends TestCase {

    private string $tmpDir = "";
    private string $tmpFile = "";


    protected function setUp(): void {
        $tmpBase = sys_get_temp_dir();
        $this->tmpDir  = $tmpBase . DIRECTORY_SEPARATOR . "fl_test_dir_" . uniqid();
        $this->tmpFile = $tmpBase . DIRECTORY_SEPARATOR . "fl_test_file_" . uniqid() . ".txt";

        @mkdir($this->tmpDir);
        @file_put_contents($this->tmpFile, "hello");
    }

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
        if ($this->tmpDir !== "" && file_exists($this->tmpDir)) {
            @rmdir($this->tmpDir);
        }
    }


    #[DataProvider("providerAdd")]
    public function testAdd(
        string $name,
        string $path,
        bool $isDir,
        string $sourcePathKey,
        string $sourceUrl,
        string $thumbPath,
        string $thumbUrl,
        array $expected,
    ): void {
        $list = new FileList();

        $sourcePath = $sourcePathKey === "tmpDir" ? $this->tmpDir : $this->tmpFile;

        $result = $list->add(
            name:       $name,
            path:       $path,
            isDir:      $isDir,
            sourcePath: $sourcePath,
            sourceUrl:  $sourceUrl,
            thumbPath:  $thumbPath,
            thumbUrl:   $thumbUrl,
        );

        $items = $list->get();

        $this->assertSame($list, $result);
        $this->assertCount(1, $items);
        $this->assertInstanceOf(FileItem::class, $items[0]);
        $this->assertSame($expected["name"], $items[0]->name);
        $this->assertSame($expected["path"], $items[0]->path);
        $this->assertSame($expected["mvPath"], $items[0]->mvPath);
        $this->assertSame($expected["canSelect"], $items[0]->canSelect);
        $this->assertSame($expected["isBack"], $items[0]->isBack);
        $this->assertSame($expected["isDir"], $items[0]->isDir);
        $this->assertSame($expected["isFile"], $items[0]->isFile);
        $this->assertSame($expected["isImage"], $items[0]->isImage);
        $this->assertSame($expected["source"], $items[0]->source);
        $this->assertSame($expected["url"], $items[0]->url);
        $this->assertSame($expected["thumb"], $items[0]->thumb);
    }

    public static function providerAdd(): array {
        return [
            "file" => [
                "notes.txt",
                "/docs/notes.txt",
                false,
                "tmpFile",
                "/files/docs/notes.txt",
                "",
                "",
                [
                    "name"      => "notes.txt",
                    "path"      => "/docs/notes.txt",
                    "mvPath"    => "/docs/notes.txt",
                    "canSelect" => true,
                    "isBack"    => false,
                    "isDir"     => false,
                    "isFile"    => true,
                    "isImage"   => false,
                    "source"    => "/files/docs/notes.txt",
                    "url"       => "/files/docs/notes.txt",
                    "thumb"     => "",
                ],
            ],
            "directory" => [
                "images",
                "/docs/images",
                true,
                "tmpDir",
                "/files/docs/images",
                "",
                "",
                [
                    "name"      => "images",
                    "path"      => "/docs/images",
                    "mvPath"    => "/docs/images",
                    "canSelect" => false,
                    "isBack"    => false,
                    "isDir"     => true,
                    "isFile"    => true,
                    "isImage"   => false,
                    "source"    => "/files/docs/images",
                    "url"       => "/files/docs/images",
                    "thumb"     => "",
                ],
            ],
        ];
    }


    #[DataProvider("providerAddBack")]
    public function testAddBack(string $path, array $expected): void {
        $list = new FileList();

        $result = $list->addBack($path);

        $items = $list->get();

        $this->assertSame($list, $result);
        $this->assertCount(1, $items);
        $this->assertInstanceOf(FileItem::class, $items[0]);
        $this->assertSame($expected["name"], $items[0]->name);
        $this->assertSame($expected["path"], $items[0]->path);
        $this->assertSame($expected["mvPath"], $items[0]->mvPath);
        $this->assertSame($expected["canSelect"], $items[0]->canSelect);
        $this->assertSame($expected["isBack"], $items[0]->isBack);
        $this->assertSame($expected["isFile"], $items[0]->isFile);
        $this->assertSame($expected["icon"], $items[0]->icon);
    }

    public static function providerAddBack(): array {
        return [
            "nested_path" => [
                "/docs/subdir",
                [
                    "name"      => "...",
                    "path"      => "/docs",
                    "mvPath"    => "/docs",
                    "canSelect" => false,
                    "isBack"    => true,
                    "isFile"    => true,
                    "icon"      => "back",
                ],
            ],
            "root_path" => [
                "/",
                [
                    "name"      => "...",
                    "path"      => "/",
                    "mvPath"    => "/",
                    "canSelect" => false,
                    "isBack"    => true,
                    "isFile"    => true,
                    "icon"      => "back",
                ],
            ],
        ];
    }


    #[DataProvider("providerGet")]
    public function testGet(array $operations, array $expectedNames): void {
        $list = new FileList();

        foreach ($operations as $operation) {
            if ($operation["method"] === "addBack") {
                $list->addBack($operation["path"]);
                continue;
            }

            $sourcePath = $operation["sourcePath"] === "tmpDir" ? $this->tmpDir : $this->tmpFile;
            $list->add(
                name:       $operation["name"],
                path:       $operation["path"],
                isDir:      $operation["isDir"],
                sourcePath: $sourcePath,
                sourceUrl:  $operation["sourceUrl"],
                thumbPath:  $operation["thumbPath"],
                thumbUrl:   $operation["thumbUrl"],
            );
        }

        $items = $list->get();

        $this->assertCount(count($expectedNames), $items);
        $this->assertSame($expectedNames, array_map(
            static fn (FileItem $item): string => $item->name,
            $items,
        ));
    }

    public static function providerGet(): array {
        return [
            "insertion_order" => [
                [
                    [ "method" => "addBack", "path" => "/docs/subdir" ],
                    [
                        "method" => "add",
                        "name" => "notes.txt",
                        "path" => "/docs/notes.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/notes.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "images",
                        "path" => "/docs/images",
                        "isDir" => true,
                        "sourcePath" => "tmpDir",
                        "sourceUrl" => "/files/docs/images",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                ],
                [ "...", "notes.txt", "images" ],
            ],
            "empty_list" => [
                [],
                [],
            ],
        ];
    }


    #[DataProvider("providerGetSorted")]
    public function testGetSorted(
        array $operations,
        array $expectedNames,
        bool $expectBackFirst,
        bool $expectDirsFirst,
    ): void {
        $list = new FileList();

        foreach ($operations as $operation) {
            if ($operation["method"] === "addBack") {
                $list->addBack($operation["path"]);
                continue;
            }

            $sourcePath = $operation["sourcePath"] === "tmpDir" ? $this->tmpDir : $this->tmpFile;
            $list->add(
                name:       $operation["name"],
                path:       $operation["path"],
                isDir:      $operation["isDir"],
                sourcePath: $sourcePath,
                sourceUrl:  $operation["sourceUrl"],
                thumbPath:  $operation["thumbPath"],
                thumbUrl:   $operation["thumbUrl"],
            );
        }

        $sorted = $list->getSorted();

        $this->assertSame(
            $expectedNames,
            array_map(static fn (FileItem $item): string => $item->name, $sorted),
        );
        if ($expectBackFirst) {
            $this->assertTrue($sorted[0]->isBack);
        }
        if ($expectDirsFirst && count($sorted) >= 3) {
            $this->assertTrue($sorted[1]->isDir);
            $this->assertTrue($sorted[2]->isDir);
        }
        if ($expectDirsFirst && count($sorted) >= 4) {
            $this->assertFalse($sorted[3]->isDir);
        }
    }

    public static function providerGetSorted(): array {
        return [
            "back_dirs_files" => [
                [
                    [
                        "method" => "add",
                        "name" => "file10.txt",
                        "path" => "/docs/file10.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/file10.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "Beta",
                        "path" => "/docs/Beta",
                        "isDir" => true,
                        "sourcePath" => "tmpDir",
                        "sourceUrl" => "/files/docs/Beta",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [ "method" => "addBack", "path" => "/docs/subdir" ],
                    [
                        "method" => "add",
                        "name" => "alpha",
                        "path" => "/docs/alpha",
                        "isDir" => true,
                        "sourcePath" => "tmpDir",
                        "sourceUrl" => "/files/docs/alpha",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "file2.txt",
                        "path" => "/docs/file2.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/file2.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "File1.txt",
                        "path" => "/docs/File1.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/File1.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                ],
                [ "...", "alpha", "Beta", "File1.txt", "file2.txt", "file10.txt" ],
                true,
                true,
            ],
            "files_only" => [
                [
                    [
                        "method" => "add",
                        "name" => "file10.txt",
                        "path" => "/docs/file10.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/file10.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "file2.txt",
                        "path" => "/docs/file2.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/file2.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                    [
                        "method" => "add",
                        "name" => "File1.txt",
                        "path" => "/docs/File1.txt",
                        "isDir" => false,
                        "sourcePath" => "tmpFile",
                        "sourceUrl" => "/files/docs/File1.txt",
                        "thumbPath" => "",
                        "thumbUrl" => "",
                    ],
                ],
                [ "File1.txt", "file2.txt", "file10.txt" ],
                false,
                false,
            ],
        ];
    }
}
