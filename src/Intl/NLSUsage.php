<?php
namespace Framework\Intl;

use Framework\File\Storage;
use Framework\Utils\Strings;

/**
 * The Language Keys used by the Source
 * @phpstan-type NLSUsageData array{
 *   words:    array<string,bool>,
 *   prefixes: array<string,bool>,
 *   calls:    array<string,string>,
 *   props:    array<string,string>,
 * }
 */
class NLSUsage {

    // Everything a key can be named from
    private const Extensions       = [ ".php", ".js", ".jsx", ".mjs", ".ts", ".tsx" ];
    private const ScriptExtensions = [ ".js", ".jsx", ".mjs", ".ts", ".tsx" ];

    /**
     * Where a key is asked for by name, which is what it is missing from. They
     * are the methods that take one, and the errors of a request, whose fields
     * are named after the input each of them is about
     * @var list<string>
     */
    private const CallPatterns = [
        '/\bNLS(?:::|\.)\w+\(\s*\[?\s*((?:["\'][A-Z][A-Z0-9_]*["\']\s*,?\s*)+)/',
        '/\bResponse::(?:success|warning|error)\(\s*(["\'][A-Z][A-Z0-9_]*["\'])/',
        '/\w*[Ee]rrors\s*->\s*add\w*\(([^()]*)\)/',
        '/\w*[Ee]rrors\s*->\s*\w+\s*=\s*(["\'][A-Z][A-Z0-9_]*["\'])/',
    ];

    // An element of an app takes its key as an attribute, since a component is
    // given the key rather than the string it stands for
    private const PropPattern = '/\b[a-zA-Z]+\s*=\s*["\']([A-Z][A-Z0-9_]{2,})["\']/';
    private const KeyPattern  = '/["\']([A-Z][A-Z0-9_]*)["\']/';

    // A key is used wherever its name shows up, since the answer of the server
    // is rendered by the app, and an email is asked for by the code it is under
    private const WordPattern = '/\b[A-Z][A-Za-z0-9_]{2,}\b/';

    // The start of a key that the code completes, as "AUTOMATION_ERRORS_{$type}"
    private const PrefixPattern = '/["\'`]([A-Z][A-Z0-9_]*_)(?:\$\{|\{\$|["\']\s*[.+])/';

    // What a plural key is written with, and reached through NLS::pluralize()
    private const PluralSuffixes = [ "_SINGULAR", "_PLURAL" ];



    /**
     * Reads the Language Keys named by the Source of the given directories
     * @param list<string> $paths
     * @param list<string> $skipPaths
     * @param bool         $withCalls
     * @return NLSUsageData
     */
    public static function read(array $paths, array $skipPaths, bool $withCalls): array {
        $result = [
            "words"    => [],
            "prefixes" => [],
            "calls"    => [],
            "props"    => [],
        ];

        foreach ($paths as $path) {
            $filePaths = Storage::getFilesInDir($path, recursive: true, skipVendor: true);
            foreach ($filePaths as $filePath) {
                if (!Strings::endsWith($filePath, ...self::Extensions)) {
                    continue;
                }

                // The Language files name every key they hold, which is not a use
                if (self::isInside($filePath, $skipPaths)) {
                    continue;
                }

                $contents = Storage::readFile($filePath);
                foreach (self::match($contents, self::WordPattern) as $word) {
                    $result["words"][$word] = true;
                }
                foreach (self::match($contents, self::PrefixPattern) as $prefix) {
                    $result["prefixes"][$prefix] = true;
                }

                if (!$withCalls) {
                    continue;
                }

                $calls = self::getCalls($contents, $filePath);
                $result["calls"] = self::mergeKeys($result["calls"], $calls);

                // Only an app writes its keys as attributes, while the same
                // thing in PHP is any string a property is given
                if (Strings::endsWith($filePath, ...self::ScriptExtensions)) {
                    $props = self::getProps($contents, $filePath);
                    $result["props"] = self::mergeKeys($result["props"], $props);
                }
            }
        }
        return $result;
    }

    /**
     * Merges the Keys of two readings, the second of which only names keys
     * @param NLSUsageData $usage
     * @param NLSUsageData $other
     * @return NLSUsageData
     */
    public static function merge(array $usage, array $other): array {
        foreach ($other["words"] as $word => $isUsed) {
            $usage["words"][$word] = $isUsed;
        }
        foreach ($other["prefixes"] as $prefix => $isUsed) {
            $usage["prefixes"][$prefix] = $isUsed;
        }
        $usage["calls"] = self::mergeKeys($usage["calls"], $other["calls"]);
        $usage["props"] = self::mergeKeys($usage["props"], $other["props"]);
        return $usage;
    }

    /**
     * Returns whether the Source names the given Key anywhere
     * @param NLSUsageData $usage
     * @param string       $key
     * @return bool
     */
    public static function isUsed(array $usage, string $key): bool {
        if (isset($usage["words"][$key])) {
            return true;
        }

        // A plural pair is reached by the key both of them are written from
        foreach (self::PluralSuffixes as $suffix) {
            $base = Strings::stripEnd($key, $suffix);
            if ($base !== $key && isset($usage["words"][$base])) {
                return true;
            }
        }

        // And a key the code completes is only named by its start
        foreach (array_keys($usage["prefixes"]) as $prefix) {
            if (Strings::startsWith($key, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the Keys the Source names that nothing defines, and where each is
     * @param NLSUsageData       $usage
     * @param array<string,bool> $defined
     * @return array<string,string>
     */
    public static function getMissing(array $usage, array $defined): array {
        $result = [];
        foreach ($usage["calls"] as $key => $where) {
            if (!self::isDefined($defined, $key)) {
                $result[$key] = $where;
            }
        }

        // An attribute holds anything an element takes, an action of its own
        // included, so only a key that could join a family already there is
        // taken for one, or every one of them would be reported as missing
        $families = self::getFamilies($defined);
        foreach ($usage["props"] as $key => $where) {
            $family = Strings::substringBefore($key, "_");
            if ($family !== $key && isset($families[$family]) && !self::isDefined($defined, $key)) {
                $result[$key] = $where;
            }
        }
        return $result;
    }



    /**
     * Returns whether the given Key is defined, on its own or as a plural pair
     * @param array<string,bool> $defined
     * @param string             $key
     * @return bool
     */
    private static function isDefined(array $defined, string $key): bool {
        if (isset($defined[$key])) {
            return true;
        }
        foreach (self::PluralSuffixes as $suffix) {
            if (isset($defined[$key . $suffix])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the start of every Key that is defined, as the families they form
     * @param array<string,bool> $defined
     * @return array<string,bool>
     */
    private static function getFamilies(array $defined): array {
        $result = [];
        foreach (array_keys($defined) as $key) {
            $family = Strings::substringBefore($key, "_");
            if ($family !== $key) {
                $result[$family] = true;
            }
        }
        return $result;
    }

    /**
     * Returns the Keys named by a call, with the place each of them is at
     * @param string $contents
     * @param string $filePath
     * @return array<string,string>
     */
    private static function getCalls(string $contents, string $filePath): array {
        $result = [];
        foreach (self::CallPatterns as $pattern) {
            foreach (self::matchAt($contents, $pattern) as $found => $offset) {
                // The arguments are taken together, since a url is built from a
                // list, and an error names the input it is about before the key
                foreach (self::match(Strings::toString($found), self::KeyPattern) as $key) {
                    // What the code completes is the start of a key, not one
                    if (!Strings::endsWith($key, "_")) {
                        $result[$key] = self::getPlace($contents, $filePath, $offset);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Keys named by an attribute, with the place each of them is at
     * @param string $contents
     * @param string $filePath
     * @return array<string,string>
     */
    private static function getProps(string $contents, string $filePath): array {
        $result = [];
        foreach (self::matchAt($contents, self::PropPattern) as $key => $offset) {
            $result[Strings::toString($key)] = self::getPlace($contents, $filePath, $offset);
        }
        return $result;
    }

    /**
     * Returns the place of the given offset, as the file and the line
     * @param string $contents
     * @param string $filePath
     * @param int    $offset
     * @return string
     */
    private static function getPlace(string $contents, string $filePath, int $offset): string {
        $line = substr_count(Strings::substring($contents, 0, $offset), "\n") + 1;
        return "$filePath:$line";
    }

    /**
     * Returns whether the given File is inside one of the given directories
     * @param string       $filePath
     * @param list<string> $paths
     * @return bool
     */
    private static function isInside(string $filePath, array $paths): bool {
        foreach ($paths as $path) {
            if (Strings::startsWith($filePath, "$path/")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds the Keys of a file to the ones already found, keeping the first place
     * @param array<string,string> $keys
     * @param array<string,string> $found
     * @return array<string,string>
     */
    private static function mergeKeys(array $keys, array $found): array {
        foreach ($found as $key => $where) {
            if (!isset($keys[$key])) {
                $keys[$key] = $where;
            }
        }
        return $keys;
    }

    /**
     * Returns the first group of every match of the given Pattern
     * @param string $contents
     * @param string $pattern
     * @return list<string>
     */
    private static function match(string $contents, string $pattern): array {
        $amount = preg_match_all($pattern, $contents, $matches);
        if ($amount === false || $amount === 0) {
            return [];
        }

        // A pattern with no group of its own answers with the whole match
        $found  = $matches[1] ?? $matches[0] ?? [];
        $result = [];
        foreach ($found as $value) {
            $result[] = Strings::toString($value);
        }
        return $result;
    }

    /**
     * Returns the first group of every match of the given Pattern, by its offset
     * @param string $contents
     * @param string $pattern
     * @return array<string,int>
     */
    private static function matchAt(string $contents, string $pattern): array {
        $amount = preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE);
        if ($amount === false || $amount === 0) {
            return [];
        }

        $result = [];
        foreach ($matches[1] ?? [] as $match) {
            $result[Strings::toString($match[0])] = $match[1];
        }
        return $result;
    }
}
