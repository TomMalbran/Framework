<?php
namespace Framework\File;

use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The File Types used by the System
 */
class FileType {

    /**
     * All the valid Values
     */
    public static $imageExts        = [ "jpg", "jpeg", "gif", "png", "ico" ];
    public static $pngExts          = [ "png" ];
    public static $videoExts        = [ "mov", "mpeg", "m4v", "mp4", "avi", "mpg", "wma", "flv", "webm" ];
    public static $audioExts        = [ "mp3", "mpga", "m4a", "ac3", "aiff", "mid", "ogg", "wav" ];
    public static $codeExts         = [ "html", "xhtml", "sql", "xml", "js", "json", "css" ];
    public static $textExts         = [ "txt", "csv", "log", "rtf" ];
    public static $documentExts     = [ "doc", "docx", "odt", "ott" ];
    public static $spreadsheetExts  = [ "xls", "xlsx", "ods" ];
    public static $presentationExts = [ "ppt", "pptx" ];
    public static $pdfExts          = [ "pdf" ];
    public static $zipExts          = [ "zip", "rar", "gz", "tar", "iso", "7zip" ];




    /**
     * Returns true if the given file is a Directory
     * @param string $file
     * @return boolean
     */
    public static function isDir($file) {
        return is_dir($file);
    }

    /**
     * Returns true if the given file is a Hidden
     * @param string $file
     * @return boolean
     */
    public static function isHidden($file) {
        return Strings::startsWith($path, ".");
    }

    /**
     * Returns true if the given file is an Image
     * @param string $file
     * @return boolean
     */
    public static function isImage($file) {
        return File::hasExtension($file, self::$imageExts);
    }

    /**
     * Returns true if the given file is a PNG
     * @param string $file
     * @return boolean
     */
    public static function isPNG($file) {
        return File::hasExtension($file, self::$pngExts);
    }

    /**
     * Returns true if the given file is a Video
     * @param string $file
     * @return boolean
     */
    public static function isVideo($file) {
        return File::hasExtension($file, self::$videoExts);
    }

    /**
     * Returns true if the given file is an Audio
     * @param string $file
     * @return boolean
     */
    public static function isAudio($file) {
        return File::hasExtension($file, self::$audioExts);
    }

    /**
     * Returns true if the given file is a Code
     * @param string $file
     * @return boolean
     */
    public static function isCode($file) {
        return File::hasExtension($file, self::$codeExts);
    }

    /**
     * Returns true if the given file is a Code
     * @param string $file
     * @return boolean
     */
    public static function isText($file) {
        return File::hasExtension($file, self::$textExts);
    }

    /**
     * Returns true if the given file is a Document
     * @param string $file
     * @return boolean
     */
    public static function isDocument($file) {
        return File::hasExtension($file, self::$documentExts);
    }

    /**
     * Returns true if the given file is a Spreadsheet
     * @param string $file
     * @return boolean
     */
    public static function isSpreadsheet($file) {
        return File::hasExtension($file, self::$spreadsheetExts);
    }

    /**
     * Returns true if the given file is a Presentation
     * @param string $file
     * @return boolean
     */
    public static function isPresentation($file) {
        return File::hasExtension($file, self::$presentationExts);
    }

    /**
     * Returns true if the given file is a PDF
     * @param string $file
     * @return boolean
     */
    public static function isPDF($file) {
        return File::hasExtension($file, self::$pdfExts);
    }

    /**
     * Returns true if the given file is a Zip
     * @param string $file
     * @return boolean
     */
    public static function isZip($file) {
        return File::hasExtension($file, self::$zipExts);
    }

    /**
     * Returns true if the given file is just a File
     * @param string $file
     * @return boolean
     */
    public static function isFile($file) {
        return (
            !self::isDir($file) &&
            !self::isImage($file) &&
            !self::isVideo($file)
        );
    }



    /**
     * Returns the icon for the given File
     * @param string $name
     * @return string
     */
    public static function getIcon($name) {
        if (self::isVideo($name)) {
            return "file-video";
        }
        if (self::isAudio($name)) {
            return "file-audio";
        }
        if (self::isCode($name)) {
            return "file-code";
        }
        if (self::isText($name)) {
            return "file-text";
        }
        if (self::isDocument($name)) {
            return "file-document";
        }
        if (self::isSpreadsheet($name)) {
            return "file-spreadsheet";
        }
        if (self::isPresentation($name)) {
            return "file-presentation";
        }
        if (self::isPDF($name)) {
            return "file-pdf";
        }
        if (self::isZip($name)) {
            return "file-zip";
        }
        return "file";
    }
}
