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
