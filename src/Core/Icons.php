<?php
namespace Framework\Core;

use Framework\Application;
use Framework\Discovery\Package;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\File\Storage;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Icons Generator
 * @phpstan-type IconSet array{
 *   folders:   list<string>,
 *   stylePath: string,
 * }
 */
class Icons {

    private const Extension = ".svg";
    private const Google    = "google";
    private const GoogleUrl = "https://fonts.google.com/icons?selected=Material+Symbols+Outlined:";
    private const Template  = "src/Core/Template/Icons.mu";

    /** @var array<string,string> */
    private const Variants = [
        "fill" => "Filled",
    ];

    private static string $sourcePath  = "";
    private static string $previewPath = "";
    private static string $mappingPath = "";
    private static string $title       = "";

    /** @var array<string,IconSet> */
    private static array $iconSets = [];



    /**
     * Sets the Directory that contains all the Icon folders
     * @param string $sourcePath
     * @return void
     */
    public static function setSource(string $sourcePath): void {
        self::$sourcePath = $sourcePath;
    }

    /**
     * Sets the Path of the generated Preview page
     * @param string $previewPath
     * @return void
     */
    public static function setPreview(string $previewPath): void {
        self::$previewPath = $previewPath;
    }

    /**
     * Sets the Path of the file that describes where each Icon comes from.
     * It can use comments, as it is only read to enrich the Preview page
     * @param string $mappingPath
     * @return void
     */
    public static function setMapping(string $mappingPath): void {
        self::$mappingPath = $mappingPath;
    }

    /**
     * Sets the Title of the Preview page
     * @param string $title
     * @return void
     */
    public static function setTitle(string $title): void {
        self::$title = $title;
    }

    /**
     * Registers the Icon Set of an App
     * @param string       $name
     * @param list<string> $folders
     * @param string       $stylePath
     * @return void
     */
    public static function register(
        string $name,
        array $folders,
        string $stylePath,
    ): void {
        if ($name === "" || count($folders) === 0 || $stylePath === "") {
            return;
        }

        self::$iconSets[$name] = [
            "folders"   => $folders,
            "stylePath" => $stylePath,
        ];
    }



    /**
     * Generates the Stylesheet of every registered Icon Set and the Preview page
     * @return void
     */
    #[ConsoleCommand("icons")]
    public static function generate(): void {
        print("Generating the icons...\n\n");
        DiscoveryConfig::load();

        if (self::$sourcePath === "") {
            print("There is no source directory. Use Icons::setSource() to set it.\n");
            return;
        }
        if (count(self::$iconSets) === 0) {
            print("There are no icon sets. Use Icons::register() to add them.\n");
            return;
        }


        // Read the Icons of every Folder used by the Icon Sets
        $folderIcons = [];
        foreach (self::getFolders() as $folder) {
            $icons  = self::getIcons($folder);
            $amount = count($icons);
            if ($amount === 0) {
                print("- There are no icons in the $folder folder\n");
                continue;
            }
            $folderIcons[$folder] = $icons;
            print("- Read $amount icons from the $folder folder\n");
        }

        // Print empty line to separate the reading from the writing
        print("\n");

        // Generate the Stylesheet of every Icon Set
        foreach (self::$iconSets as $name => $iconSet) {
            $icons = [];
            foreach ($iconSet["folders"] as $folder) {
                $icons = Arrays::merge($icons, $folderIcons[$folder] ?? []);
            }
            ksort($icons);

            $stylePath = Application::getBasePath($iconSet["stylePath"]);
            self::writeFile($stylePath, self::getStyle($icons));

            $amount = count($icons);
            print("- Created the $name stylesheet with $amount icons\n");
        }

        // Generate the Preview page with the Icons of every Folder
        if (self::$previewPath !== "") {
            $filePath = Application::getBasePath(self::$previewPath);
            $content  = self::getPreview($folderIcons);
            self::writeFile($filePath, $content);
            print("- Created the preview page\n");
        }
    }



    /**
     * Writes the given content, creating the directory when required
     * @param string $filePath
     * @param string $content
     * @return void
     */
    private static function writeFile(string $filePath, string $content): void {
        Storage::createDir(Storage::getDirectory($filePath));
        Storage::writeFile($filePath, $content);
    }

    /**
     * Returns the unique Folders used by the Icon Sets, in the registered order
     * @return list<string>
     */
    private static function getFolders(): array {
        $result = [];
        foreach (self::$iconSets as $iconSet) {
            foreach ($iconSet["folders"] as $folder) {
                if (!Arrays::contains($result, $folder)) {
                    $result[] = $folder;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Icons of the given Folder as a name to Data Url map
     * @param string $folder
     * @return array<string,string>
     */
    private static function getIcons(string $folder): array {
        $basePath = Application::getBasePath(self::$sourcePath, $folder);
        $result   = [];

        foreach (Storage::getFilesInDir($basePath) as $fileName) {
            if (!Strings::endsWith($fileName, self::Extension)) {
                continue;
            }

            $content = Storage::readFile($basePath, $fileName);
            if ($content === "") {
                continue;
            }

            $name          = Strings::replaceEnd($fileName, self::Extension, "");
            $result[$name] = self::getDataUrl($content);
        }

        ksort($result);
        return $result;
    }

    /**
     * Returns the Svg content as a Data Url
     * @param string $content
     * @return string
     */
    private static function getDataUrl(string $content): string {
        $svg = Strings::replacePattern($content, "/\s+/", " ");
        $svg = Strings::trim($svg);
        return "data:image/svg+xml;base64," . base64_encode($svg);
    }



    /**
     * Returns the Stylesheet for the given Icons
     * @param array<string,string> $icons
     * @return string
     */
    private static function getStyle(array $icons): string {
        $result = Strings::join([
            '[class^="icon-"]:before,',
            '[class*=" icon-"]:before {',
            '    content: "";',
            "    display: inline-block;",
            "    width: 1em;",
            "    height: 1em;",
            "    vertical-align: baseline;",
            "    background-color: currentColor;",
            "    -webkit-mask: var(--icon) no-repeat center / contain;",
            "    mask: var(--icon) no-repeat center / contain;",
            "}",
            "",
            "",
        ], "\n");

        foreach ($icons as $name => $dataUrl) {
            $result .= ".icon-$name:before { --icon: url(\"$dataUrl\"); }\n";
        }
        return $result;
    }

    /**
     * Returns the Icon descriptions of the mapping file, indexed by folder and name
     * @return array<string,array<string,string>>
     */
    private static function getMapping(): array {
        if (self::$mappingPath === "") {
            return [];
        }

        $filePath = Application::getBasePath(self::$mappingPath);
        if (!Storage::fileExists($filePath)) {
            return [];
        }

        // Remove the comments, so a json file with them can be used
        $contents = Storage::readFile($filePath);
        $contents = Strings::replacePattern($contents, "/^\s*\/\/.*$/m", "");

        $result = [];
        foreach (JSON::decodeAsArray($contents) as $folder => $icons) {
            if (!is_string($folder) || !is_array($icons)) {
                continue;
            }
            foreach ($icons as $name => $value) {
                $result[$folder][Strings::toString($name)] = Strings::toString($value);
            }
        }
        return $result;
    }

    /**
     * Returns the Preview page with the Icons of every Folder
     * @param array<string,array<string,string>> $folderIcons
     * @return string
     */
    private static function getPreview(array $folderIcons): string {
        $icons    = [];
        $folders  = [];
        $sources  = [];
        $variants = [];

        $mapping = self::getMapping();

        foreach ($folderIcons as $folder => $folderIcon) {
            $icons = Arrays::merge($icons, $folderIcon);
            $names = [];

            foreach (array_keys($folderIcon) as $name) {
                $value   = $mapping[$folder][$name] ?? "";
                $source  = Strings::substringBefore($value, " ");
                $detail  = "";
                if ($source !== "") {
                    $detail = Strings::trim(Strings::stripStart($value, $source));
                }
                $matName   = Strings::substringBefore($detail, " ");
                $modifiers = Strings::trim(Strings::stripStart($detail, $matName));

                // Only the Material icons can link to the Google page
                $link = "";
                if ($source === self::Google && $matName !== "") {
                    $link = self::GoogleUrl . $matName;
                }

                // Gather the Tags used to filter the Icons
                $tags = [];
                if ($source !== "") {
                    $sources[$source] = ($sources[$source] ?? 0) + 1;
                    $tags[] = $source;
                }
                foreach (Strings::split($modifiers, " ", skipEmpty: true) as $variant) {
                    $variants[$variant] = ($variants[$variant] ?? 0) + 1;
                    $tags[] = $variant;
                }

                $names[] = [
                    "name"      => $name,
                    "source"    => $source,
                    "matName"   => $matName,
                    "modifiers" => $modifiers,
                    "link"      => $link,
                    "tags"      => Strings::join($tags, " "),
                ];
            }

            $folders[] = [
                "name"   => $folder,
                "amount" => count($folderIcon),
                "icons"  => $names,
            ];
        }

        $filters = [
            [
                "title" => "Sources",
                "items" => self::getFilters("sources", $sources),
            ],
            [
                "title" => "Variants",
                "items" => self::getFilters("variants", $variants),
            ],
        ];

        $template = Storage::readFile(Package::getBasePath(self::Template));
        return Mustache::render($template, [
            "project" => self::getTitle(),
            "style"   => self::getStyle($icons),
            "amount"  => count($icons),
            "filters" => $filters,
            "folders" => $folders,
        ]);
    }

    /**
     * Returns the Filters of a Group for the given Tag amounts, with the most used first
     * @param string            $group
     * @param array<string,int> $amounts
     * @return list<array{group:string,name:string,title:string,amount:int}>
     */
    private static function getFilters(string $group, array $amounts): array {
        $amounts = Arrays::sort($amounts, fn(int $a, int $b) => $b <=> $a);

        $result = [];
        foreach ($amounts as $name => $amount) {
            $result[] = [
                "group"  => $group,
                "name"   => Strings::toString($name),
                "title"  => self::getFilterTitle(Strings::toString($name)),
                "amount" => $amount,
            ];
        }
        return $result;
    }

    /**
     * Returns the Title of the Preview page
     * @return string
     */
    private static function getTitle(): string {
        if (self::$title !== "") {
            return self::$title;
        }
        return Application::getName();
    }

    /**
     * Returns the Title to display for the given Tag
     * @param string $name
     * @return string
     */
    private static function getFilterTitle(string $name): string {
        if (Strings::match($name, "/^w\d+$/")) {
            return "Weight " . Strings::stripStart($name, "w");
        }
        if (Strings::match($name, "/^s\d+$/")) {
            return "Size " . Strings::stripStart($name, "s");
        }
        return self::Variants[$name] ?? Strings::upperCaseFirst($name);
    }
}
