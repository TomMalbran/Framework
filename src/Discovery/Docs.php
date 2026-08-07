<?php
namespace Framework\Discovery;

use Framework\Discovery\Package;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\File\Storage;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Documentation site
 */
class Docs {

    private const SourceUrl = "https://github.com/TomMalbran/Framework/blob/main/";



    /**
     * Serves the Documentation site in the local machine
     * @param int $port Optional.
     * @return void
     */
    #[ConsoleCommand("docs", isPrivate: true)]
    public static function serve(int $port = Package::DocsPort): void {
        $docsPath = Package::getBasePath(Package::DocsDir);
        if (!Storage::fileExists($docsPath)) {
            print("There is no documentation to serve\n");
            return;
        }

        $url = "localhost:$port";
        print("Serving the docs at http://$url\n");
        print("Press Ctrl+C to stop\n\n");

        $command = "php -S " . escapeshellarg($url) . " -t " . escapeshellarg($docsPath);
        if (PHP_OS_FAMILY !== "Windows") {
            // Drop the request logs, so only the errors are printed
            $command .= " 2>&1 | grep --line-buffered -vE '(Accepted|Closing|\\[200\\]:)'";
        }
        passthru($command);
    }

    /**
     * Checks that every link in the Documentation still resolves
     * @return void
     */
    #[ConsoleCommand("docsCheck", isPrivate: true)]
    public static function check(): void {
        $docsPath = Package::getBasePath(Package::DocsDir);
        if (!Storage::fileExists($docsPath)) {
            print("There is no documentation to check\n");
            return;
        }

        $pages    = self::getPages($docsPath);
        $contents = [];
        foreach ($pages as $page) {
            $contents[$page] = Storage::readFile($docsPath, $page);
        }

        $broken  = self::checkSourceLinks($contents);
        $broken += self::checkPageLinks($docsPath, $contents);
        $broken += self::checkCodeExamples($contents);
        $broken += self::checkSearchIndex($docsPath);

        $total = count($pages);
        if ($broken === 0) {
            print("- Checked $total documentation pages, everything resolves\n");
        } else {
            $problems = $broken === 1 ? "problem" : "problems";
            print("- Found $broken $problems in $total documentation pages\n");
        }
    }

    /**
     * Rebuilds the search index of the Documentation
     * @return void
     */
    #[ConsoleCommand("docsIndex", isPrivate: true)]
    public static function index(): void {
        $docsPath = Package::getBasePath(Package::DocsDir);
        if (!Storage::fileExists($docsPath)) {
            print("There is no documentation to index\n");
            return;
        }

        $index = self::buildIndex($docsPath);

        // Encoding returns an empty string when it fails, which would wipe the index
        $encoded = JSON::encode($index);
        if ($encoded === "") {
            print("- Could not encode the search index, it was left untouched\n");
            return;
        }

        Storage::writeFile(self::getIndexPath(), $encoded);

        $total = count($index);
        print("- Indexed $total documentation entries\n");
    }



    /**
     * Builds the search index of the Documentation
     * @param string $docsPath
     * @return list<array<string,string>>
     */
    private static function buildIndex(string $docsPath): array {
        $index = [];
        foreach (self::getPages($docsPath) as $page) {
            // The 404 page is served for unknown paths, it is not a destination
            if ($page === "404.html") {
                continue;
            }

            $contents = Storage::readFile($docsPath, $page);
            $title    = self::getFirstMatch($contents, '~<title>([^<—]*)~', $page);
            $section  = self::getFirstMatch($contents, '~<div class="eyebrow">([^<]*)~', "");
            $body     = self::getBody($contents);
            $lead     = self::getFirstMatch($body, '~<p class="lead">(.*?)</p>~s', "");

            // One entry for the page, described by its lead paragraph
            $index[] = [
                "t" => Strings::trim($title),
                "s" => Strings::trim($section),
                "u" => $page,
                "d" => self::getSnippet($lead),
            ];

            // And one per section, described by the text that follows it
            $pattern  = '~<h([23]) id="([^"]+)"[^>]*>(.*?)</h\1>(.*?)(?=<h[23] id=|$)~s';
            $sections = self::matchSets($body, $pattern);
            foreach ($sections as $found) {
                $heading = self::toText(self::getGroup($found, 3));
                if ($heading === "") {
                    continue;
                }
                $index[] = [
                    "t" => $heading,
                    "s" => Strings::trim($title),
                    "u" => $page . "#" . self::getGroup($found, 2),
                    "d" => self::getSnippet(self::getGroup($found, 4)),
                ];
            }
        }
        return $index;
    }

    /**
     * Returns the path of the search index
     * @return string
     */
    private static function getIndexPath(): string {
        return Package::getBasePath(Package::DocsDir, "assets", "search.json");
    }

    /**
     * Checks that the search index matches the pages it was built from
     * @param string $docsPath
     * @return int
     */
    private static function checkSearchIndex(string $docsPath): int {
        $expected = JSON::encode(self::buildIndex($docsPath));
        if ($expected === "") {
            print("  The search index cannot be encoded from the current pages\n");
            return 1;
        }

        $current = Storage::readFile(self::getIndexPath());
        if ($current === $expected) {
            return 0;
        }

        print("  The search index is out of date, run docsIndex to rebuild it\n");
        return 1;
    }



    /**
     * Checks the links that point at a source file on GitHub
     * @param array<string,string> $contents
     * @return int
     */
    private static function checkSourceLinks(array $contents): int {
        $basePath = Package::getBasePath();
        $broken   = 0;

        foreach ($contents as $page => $body) {
            $pattern = '~href="' . preg_quote(self::SourceUrl, "~") .
                '([^"]+)"[^>]*><code>([^<]+)</code>~';

            foreach (self::matchSets($body, $pattern) as $found) {
                $filePath = self::getGroup($found, 1);
                if (!Storage::fileExists($basePath, $filePath)) {
                    print("  $page -> $filePath does not exist\n");
                    $broken += 1;
                    continue;
                }

                // A label like "Class::method()" has to name a method of that file. The
                // parentheses are what tells it apart from an enum case or a constant.
                $method = self::getFirstMatch(self::getGroup($found, 2), '~::(\w+)\(~', "");
                if ($method === "") {
                    continue;
                }

                $source = Storage::readFile($basePath, $filePath);
                if (!Strings::contains($source, "function $method(")) {
                    print("  $page -> $filePath has no $method()\n");
                    $broken += 1;
                }
            }
        }
        return $broken;
    }

    /**
     * Checks the links between the Documentation pages, and their anchors
     * @param string               $docsPath
     * @param array<string,string> $contents
     * @return int
     */
    private static function checkPageLinks(string $docsPath, array $contents): int {
        $anchors = [];
        foreach ($contents as $page => $body) {
            $ids = [];
            foreach (self::matchSets($body, '~\bid="([^"]+)"~') as $found) {
                $ids[self::getGroup($found, 1)] = true;
            }
            $anchors[$page] = $ids;
        }

        $broken = 0;
        foreach ($contents as $page => $body) {
            $pattern = '~href="(?!https?:|mailto:)([^"#]*)(?:#([^"]*))?"~';

            foreach (self::matchSets($body, $pattern) as $found) {
                // An empty path means the link points inside the same page
                $link   = self::getGroup($found, 1);
                $target = $link === "" ? $page : self::resolveLink($page, $link);
                if (!isset($anchors[$target])) {
                    // Not a page, so it must be an asset that is actually there
                    if (!Storage::fileExists($docsPath, $target)) {
                        print("  $page -> $target does not exist\n");
                        $broken += 1;
                    }
                    continue;
                }

                $anchor = self::getGroup($found, 2);
                if ($anchor !== "" && !isset($anchors[$target][$anchor])) {
                    print("  $page -> $target#$anchor is not an anchor of that page\n");
                    $broken += 1;
                }
            }
        }
        return $broken;
    }

    /**
     * Checks the Framework classes and methods used by the code examples
     * @param array<string,string> $contents
     * @return int
     */
    private static function checkCodeExamples(array $contents): int {
        $snippetPattern = '~<pre><code class="language-php">(.*?)</code></pre>~s';
        $importPattern  = '~^\s*use (Framework\\\\[A-Za-z0-9_\\\\]+);~m';
        $callPattern    = '~\b([A-Z][A-Za-z0-9_]*)::([a-z][A-Za-z0-9_]*)\s*\(~';
        $broken         = 0;

        foreach ($contents as $page => $body) {
            foreach (self::matchSets($body, $snippetPattern) as $snippet) {
                $encoded = self::getGroup($snippet, 1);
                $code    = html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5, "UTF-8");

                // The classes the example imports have to exist
                $imports = [];
                foreach (self::matchSets($code, $importPattern) as $found) {
                    $class = self::getGroup($found, 1);
                    $short = Strings::substringAfter($class, "\\");

                    // Everything under System is written by the build of each app
                    if (Strings::startsWith($class, "Framework\\System\\")) {
                        continue;
                    }
                    if (!self::typeExists($class)) {
                        print("  $page -> $class does not exist\n");
                        $broken += 1;
                        continue;
                    }
                    $imports[$short] = $class;
                }

                // And the static calls on them have to be real methods
                foreach (self::matchSets($code, $callPattern) as $found) {
                    $class = $imports[self::getGroup($found, 1)] ?? "";
                    if ($class === "") {
                        continue;
                    }

                    $method = self::getGroup($found, 2);
                    if (!method_exists($class, $method)) {
                        print("  $page -> $class has no $method()\n");
                        $broken += 1;
                    }
                }
            }
        }
        return $broken;
    }

    /**
     * Returns whether the given name is a Class, Interface, Trait or Enum
     * @param string $name
     * @return bool
     */
    private static function typeExists(string $name): bool {
        return class_exists($name) || interface_exists($name) ||
            trait_exists($name) || enum_exists($name);
    }

    /**
     * Resolves a relative Link against the page that contains it
     * @param string $page
     * @param string $link
     * @return string
     */
    private static function resolveLink(string $page, string $link): string {
        $parts = Strings::split($page, "/");
        array_pop($parts);

        foreach (Strings::split($link, "/") as $part) {
            if ($part === "..") {
                array_pop($parts);
            } elseif ($part !== "." && $part !== "") {
                $parts[] = $part;
            }
        }
        return implode("/", $parts);
    }

    /**
     * Returns the path of every Documentation page, relative to the docs directory
     * @param string $docsPath
     * @return list<string>
     */
    private static function getPages(string $docsPath): array {
        $result = [];
        foreach (Storage::getFilesInDir($docsPath, recursive: true) as $filePath) {
            if (Strings::endsWith($filePath, ".html")) {
                $result[] = Strings::replace($filePath, "$docsPath/", "");
            }
        }
        return $result;
    }

    /**
     * Returns the article of the given page, or everything the page holds
     * @param string $contents
     * @return string
     */
    private static function getBody(string $contents): string {
        $pattern = '~<article class="doc-main prose">(.*?)<div class="page-nav">~s';
        $article = self::getFirstMatch($contents, $pattern, "");
        return $article !== "" ? $article : $contents;
    }

    /**
     * Returns the plain text of the given html, as the start of a description
     * @param string $contents
     * @return string
     */
    private static function getSnippet(string $contents): string {
        // Cutting by bytes would split a character and make the index invalid utf8
        return Strings::substring(self::toText($contents), 0, 180, asUtf8: true);
    }

    /**
     * Returns the plain text of the given html, without the code blocks
     * @param string $contents
     * @return string
     */
    private static function toText(string $contents): string {
        $result = Strings::replacePattern($contents, '~<pre\b.*?</pre>~s', " ");
        $result = html_entity_decode(strip_tags($result), ENT_QUOTES | ENT_HTML5, "UTF-8");
        return Strings::trim(Strings::replacePattern($result, '/\s+/u', " "));
    }

    /**
     * Returns the first group of the first match, or the given default
     * @param string $contents
     * @param string $pattern
     * @param string $default
     * @return string
     */
    private static function getFirstMatch(
        string $contents,
        string $pattern,
        string $default,
    ): string {
        $found = self::matchSets($contents, $pattern);
        return isset($found[0][1]) ? $found[0][1] : $default;
    }

    /**
     * Returns the given group of a match, which is empty when it did not participate
     * @param array<int,string> $match
     * @param int               $group
     * @return string
     */
    private static function getGroup(array $match, int $group): string {
        return $match[$group] ?? "";
    }

    /**
     * Returns every match of the given Pattern, as a list of its groups
     * @param string $contents
     * @param string $pattern
     * @return list<array<int,string>>
     */
    private static function matchSets(string $contents, string $pattern): array {
        $amount = preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);
        if ($amount === false || $amount === 0) {
            return [];
        }

        $result = [];
        foreach ($matches as $match) {
            $groups = [];
            foreach ($match as $index => $group) {
                if (is_int($index)) {
                    $groups[$index] = Strings::toString($group);
                }
            }
            $result[] = $groups;
        }
        return $result;
    }
}
