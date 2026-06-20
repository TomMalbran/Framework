<?php
namespace Framework\Utils;

// getallheaders shim for CLI tests
if (!function_exists("getallheaders")) {
    function getallheaders() {
        global $test_getallheaders;
        return $test_getallheaders ?? [];
    }
}

// file_get_contents shim to provide test inputs for php://input and synthetic test URLs
function file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $maxLen = null) {
    if ($filename === "php://input") {
        global $test_file_get_contents;
        return $test_file_get_contents ?? [];
    }
    if (is_string($filename) && str_starts_with($filename, "test://post")) {
        $opts = is_resource($context) ? stream_context_get_options($context) : (is_array($context) ? $context : []);
        $GLOBALS["test_post_url"] = $filename;
        $GLOBALS["test_post_options"] = $opts;
        return '{"ok":true}';
    }
    return \file_get_contents($filename, $use_include_path, $context, $offset, $maxLen);
}

namespace Framework\File;

function resolve_test_image_url(string $filename): string {
    $host = parse_url($filename, PHP_URL_HOST);
    if ($host !== "image.test") {
        return $filename;
    }

    $path = parse_url($filename, PHP_URL_PATH);
    if (!is_string($path)) {
        return $filename;
    }

    $fileName = rawurldecode(basename($path));
    $files    = $GLOBALS["test_image_url_files"] ?? [];
    if (is_array($files) && isset($files[$fileName]) && is_string($files[$fileName])) {
        return $files[$fileName];
    }
    return $filename;
}

// image metadata shims for synthetic test image URLs in CLI tests
if (!function_exists("Framework\\File\\exif_imagetype")) {
    function exif_imagetype(string $filename): int|false {
        return \exif_imagetype(resolve_test_image_url($filename));
    }
}

if (!function_exists("Framework\\File\\getimagesize")) {
    function getimagesize(string $filename, ?array &$image_info = null): array|false {
        $filename = resolve_test_image_url($filename);
        if ($image_info !== null) {
            return \getimagesize($filename, $image_info);
        }
        return \getimagesize($filename);
    }
}

// file_get_contents shim for encoded local file URLs in CLI tests
if (!function_exists("Framework\\File\\file_get_contents")) {
    function file_get_contents(
        string $filename,
        bool $use_include_path = false,
        mixed $context = null,
        int $offset = 0,
        ?int $maxLen = null,
    ): string|false {
        if (str_starts_with($filename, "file://")) {
            $filename = str_replace("%20", " ", $filename);
        }

        if ($maxLen !== null) {
            return \file_get_contents($filename, $use_include_path, $context, $offset, $maxLen);
        }
        return \file_get_contents($filename, $use_include_path, $context, $offset);
    }
}

// move_uploaded_file shim for CLI tests
if (!function_exists("Framework\\File\\move_uploaded_file")) {
    function move_uploaded_file(string $from, string $to): bool {
        if (($GLOBALS["test_move_uploaded_file"] ?? false) === true) {
            if ($from === "" || $to === "" || !is_file($from)) {
                return false;
            }

            if (@rename($from, $to)) {
                return true;
            }
            if (@copy($from, $to)) {
                @unlink($from);
                return true;
            }
            return false;
        }
        return \move_uploaded_file($from, $to);
    }
}
