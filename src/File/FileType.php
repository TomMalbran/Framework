<?php
// spell-checker: ignore  msvideo, binhex, compactpro, coreldraw, troff, macbinary, xbitmap, pkix, msdownload, msexcel, flac, gtar, pjpeg, mpegurl
// spell-checker: ignore  quicktime, powerpoint, msword, photoshop, realaudio, realvideo, stuffit, smil, vcard, videolan, wbxml, wmlc, xspf, scriptzsh
namespace Framework\File;

use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The File Types used by the System
 */
class FileType {

    /** @var string[] */
    public static array $imageExts        = [ "jpg", "jpeg", "gif", "png", "ico", "avif", "webp" ];

    /** @var string[] */
    public static array $pngExts          = [ "png" ];

    /** @var string[] */
    public static array $icoExts          = [ "ico" ];

    /** @var string[] */
    public static array $videoExts        = [ "mov", "mpeg", "m4v", "mp4", "avi", "mpg", "wma", "flv", "webm" ];

    /** @var string[] */
    public static array $audioExts        = [ "mp3", "mpga", "m4a", "ac3", "aiff", "mid", "ogg", "wav" ];

    /** @var string[] */
    public static array $codeExts         = [ "html", "xhtml", "sql", "xml", "js", "json", "css" ];

    /** @var string[] */
    public static array $textExts         = [ "txt", "csv", "log", "rtf", "json" ];

    /** @var string[] */
    public static array $documentExts     = [ "doc", "docx", "odt", "ott" ];

    /** @var string[] */
    public static array $spreadsheetExts  = [ "xls", "xlsx", "ods" ];

    /** @var string[] */
    public static array $presentationExts = [ "ppt", "pptx" ];

    /** @var string[] */
    public static array $pdfExts          = [ "pdf" ];

    /** @var string[] */
    public static array $zipExts          = [ "zip", "rar", "gz", "tar", "iso", "7zip" ];



    /**
     * Returns true if the given file is a Directory
     * @param string $file
     * @return boolean
     */
    public static function isDir(string $file): bool {
        return is_dir($file);
    }

    /**
     * Returns true if the given file is a Hidden
     * @param string $file
     * @return boolean
     */
    public static function isHidden(string $file): bool {
        return Strings::startsWith($file, ".");
    }

    /**
     * Returns true if the given file is an Image
     * @param string $file
     * @return boolean
     */
    public static function isImage(string $file): bool {
        return File::hasExtension($file, self::$imageExts);
    }

    /**
     * Returns true if the given file is a PNG
     * @param string $file
     * @return boolean
     */
    public static function isPNG(string $file): bool {
        return File::hasExtension($file, self::$pngExts);
    }

    /**
     * Returns true if the given file is a ICO
     * @param string $file
     * @return boolean
     */
    public static function isICO(string $file): bool {
        return File::hasExtension($file, self::$icoExts);
    }

    /**
     * Returns true if the given file is a Video
     * @param string $file
     * @return boolean
     */
    public static function isVideo(string $file): bool {
        return File::hasExtension($file, self::$videoExts);
    }

    /**
     * Returns true if the given file is an Audio
     * @param string $file
     * @return boolean
     */
    public static function isAudio(string $file): bool {
        return File::hasExtension($file, self::$audioExts);
    }

    /**
     * Returns true if the given file is a Code
     * @param string $file
     * @return boolean
     */
    public static function isCode(string $file): bool {
        return File::hasExtension($file, self::$codeExts);
    }

    /**
     * Returns true if the given file is a Code
     * @param string $file
     * @return boolean
     */
    public static function isText(string $file): bool {
        return File::hasExtension($file, self::$textExts);
    }

    /**
     * Returns true if the given file is a Document
     * @param string $file
     * @return boolean
     */
    public static function isDocument(string $file): bool {
        return File::hasExtension($file, self::$documentExts);
    }

    /**
     * Returns true if the given file is a Spreadsheet
     * @param string $file
     * @return boolean
     */
    public static function isSpreadsheet(string $file): bool {
        return File::hasExtension($file, self::$spreadsheetExts);
    }

    /**
     * Returns true if the given file is a Presentation
     * @param string $file
     * @return boolean
     */
    public static function isPresentation(string $file): bool {
        return File::hasExtension($file, self::$presentationExts);
    }

    /**
     * Returns true if the given file is a PDF
     * @param string $file
     * @return boolean
     */
    public static function isPDF(string $file): bool {
        return File::hasExtension($file, self::$pdfExts);
    }

    /**
     * Returns true if the given file is a Zip
     * @param string $file
     * @return boolean
     */
    public static function isZip(string $file): bool {
        return File::hasExtension($file, self::$zipExts);
    }

    /**
     * Returns true if the given file is just a File
     * @param string $file
     * @return boolean
     */
    public static function isFile(string $file): bool {
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
    public static function getIcon(string $name): string {
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

    /**
     * Returns an Extension for the given Mime Type
     * @param string $mimeType
     * @return string
     */
    public static function getExtension(string $mimeType): string {
        $mimeMap = [
            "video/3gpp2"                                                               => "3g2",
            "video/3gp"                                                                 => "3gp",
            "video/3gpp"                                                                => "3gp",
            "application/x-compressed"                                                  => "7zip",
            "audio/x-acc"                                                               => "aac",
            "audio/ac3"                                                                 => "ac3",
            "application/postscript"                                                    => "ai",
            "audio/x-aiff"                                                              => "aif",
            "audio/aiff"                                                                => "aif",
            "audio/x-au"                                                                => "au",
            "video/x-msvideo"                                                           => "avi",
            "video/msvideo"                                                             => "avi",
            "video/avi"                                                                 => "avi",
            "application/x-troff-msvideo"                                               => "avi",
            "application/macbinary"                                                     => "bin",
            "application/mac-binary"                                                    => "bin",
            "application/x-binary"                                                      => "bin",
            "application/x-macbinary"                                                   => "bin",
            "image/bmp"                                                                 => "bmp",
            "image/x-bmp"                                                               => "bmp",
            "image/x-bitmap"                                                            => "bmp",
            "image/x-xbitmap"                                                           => "bmp",
            "image/x-win-bitmap"                                                        => "bmp",
            "image/x-windows-bmp"                                                       => "bmp",
            "image/ms-bmp"                                                              => "bmp",
            "image/x-ms-bmp"                                                            => "bmp",
            "application/bmp"                                                           => "bmp",
            "application/x-bmp"                                                         => "bmp",
            "application/x-win-bitmap"                                                  => "bmp",
            "application/cdr"                                                           => "cdr",
            "application/coreldraw"                                                     => "cdr",
            "application/x-cdr"                                                         => "cdr",
            "application/x-coreldraw"                                                   => "cdr",
            "image/cdr"                                                                 => "cdr",
            "image/x-cdr"                                                               => "cdr",
            "zz-application/zz-winassoc-cdr"                                            => "cdr",
            "application/mac-compactpro"                                                => "cpt",
            "application/pkix-crl"                                                      => "crl",
            "application/pkcs-crl"                                                      => "crl",
            "application/x-x509-ca-cert"                                                => "crt",
            "application/pkix-cert"                                                     => "crt",
            "text/css"                                                                  => "css",
            "text/x-comma-separated-values"                                             => "csv",
            "text/comma-separated-values"                                               => "csv",
            "application/vnd.msexcel"                                                   => "csv",
            "application/x-director"                                                    => "dcr",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document"   => "docx",
            "application/x-dvi"                                                         => "dvi",
            "message/rfc822"                                                            => "eml",
            "application/x-msdownload"                                                  => "exe",
            "video/x-f4v"                                                               => "f4v",
            "audio/x-flac"                                                              => "flac",
            "video/x-flv"                                                               => "flv",
            "image/gif"                                                                 => "gif",
            "application/gpg-keys"                                                      => "gpg",
            "application/x-gtar"                                                        => "gtar",
            "application/x-gzip"                                                        => "gzip",
            "application/mac-binhex40"                                                  => "hqx",
            "application/mac-binhex"                                                    => "hqx",
            "application/x-binhex40"                                                    => "hqx",
            "application/x-mac-binhex40"                                                => "hqx",
            "text/html"                                                                 => "html",
            "image/x-icon"                                                              => "ico",
            "image/x-ico"                                                               => "ico",
            "image/vnd.microsoft.icon"                                                  => "ico",
            "text/calendar"                                                             => "ics",
            "application/java-archive"                                                  => "jar",
            "application/x-java-application"                                            => "jar",
            "application/x-jar"                                                         => "jar",
            "image/jp2"                                                                 => "jp2",
            "video/mj2"                                                                 => "jp2",
            "image/jpx"                                                                 => "jp2",
            "image/jpm"                                                                 => "jp2",
            "image/jpeg"                                                                => "jpeg",
            "image/pjpeg"                                                               => "jpeg",
            "application/x-javascript"                                                  => "js",
            "application/json"                                                          => "json",
            "text/json"                                                                 => "json",
            "application/vnd.google-earth.kml+xml"                                      => "kml",
            "application/vnd.google-earth.kmz"                                          => "kmz",
            "text/x-log"                                                                => "log",
            "audio/x-m4a"                                                               => "m4a",
            "audio/mp4"                                                                 => "m4a",
            "application/vnd.mpegurl"                                                   => "m4u",
            "audio/midi"                                                                => "mid",
            "application/vnd.mif"                                                       => "mif",
            "video/quicktime"                                                           => "mov",
            "video/x-sgi-movie"                                                         => "movie",
            "audio/mpeg"                                                                => "mp3",
            "audio/mpg"                                                                 => "mp3",
            "audio/mpeg3"                                                               => "mp3",
            "audio/mp3"                                                                 => "mp3",
            "video/mp4"                                                                 => "mp4",
            "video/mpeg"                                                                => "mpeg",
            "application/oda"                                                           => "oda",
            "audio/ogg"                                                                 => "ogg",
            "video/ogg"                                                                 => "ogg",
            "application/ogg"                                                           => "ogg",
            "font/otf"                                                                  => "otf",
            "application/x-pkcs10"                                                      => "p10",
            "application/pkcs10"                                                        => "p10",
            "application/x-pkcs12"                                                      => "p12",
            "application/x-pkcs7-signature"                                             => "p7a",
            "application/pkcs7-mime"                                                    => "p7c",
            "application/x-pkcs7-mime"                                                  => "p7c",
            "application/x-pkcs7-certreqresp"                                           => "p7r",
            "application/pkcs7-signature"                                               => "p7s",
            "application/pdf"                                                           => "pdf",
            "application/octet-stream"                                                  => "pdf",
            "application/x-x509-user-cert"                                              => "pem",
            "application/x-pem-file"                                                    => "pem",
            "application/pgp"                                                           => "pgp",
            "application/x-httpd-php"                                                   => "php",
            "application/php"                                                           => "php",
            "application/x-php"                                                         => "php",
            "text/php"                                                                  => "php",
            "text/x-php"                                                                => "php",
            "application/x-httpd-php-source"                                            => "php",
            "image/png"                                                                 => "png",
            "image/x-png"                                                               => "png",
            "application/powerpoint"                                                    => "ppt",
            "application/vnd.ms-powerpoint"                                             => "ppt",
            "application/vnd.ms-office"                                                 => "ppt",
            "application/msword"                                                        => "doc",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "application/x-photoshop"                                                   => "psd",
            "image/vnd.adobe.photoshop"                                                 => "psd",
            "audio/x-realaudio"                                                         => "ra",
            "audio/x-pn-realaudio"                                                      => "ram",
            "application/x-rar"                                                         => "rar",
            "application/rar"                                                           => "rar",
            "application/x-rar-compressed"                                              => "rar",
            "audio/x-pn-realaudio-plugin"                                               => "rpm",
            "application/x-pkcs7"                                                       => "rsa",
            "text/rtf"                                                                  => "rtf",
            "text/richtext"                                                             => "rtx",
            "video/vnd.rn-realvideo"                                                    => "rv",
            "application/x-stuffit"                                                     => "sit",
            "application/smil"                                                          => "smil",
            "text/srt"                                                                  => "srt",
            "image/svg+xml"                                                             => "svg",
            "application/x-shockwave-flash"                                             => "swf",
            "application/x-tar"                                                         => "tar",
            "application/x-gzip-compressed"                                             => "tgz",
            "image/tiff"                                                                => "tiff",
            "font/ttf"                                                                  => "ttf",
            "text/plain"                                                                => "txt",
            "text/x-vcard"                                                              => "vcf",
            "application/videolan"                                                      => "vlc",
            "text/vtt"                                                                  => "vtt",
            "audio/x-wav"                                                               => "wav",
            "audio/wave"                                                                => "wav",
            "audio/wav"                                                                 => "wav",
            "application/wbxml"                                                         => "wbxml",
            "video/webm"                                                                => "webm",
            "image/webp"                                                                => "webp",
            "audio/x-ms-wma"                                                            => "wma",
            "application/wmlc"                                                          => "wmlc",
            "video/x-ms-wmv"                                                            => "wmv",
            "video/x-ms-asf"                                                            => "wmv",
            "font/woff"                                                                 => "woff",
            "font/woff2"                                                                => "woff2",
            "application/xhtml+xml"                                                     => "xhtml",
            "application/excel"                                                         => "xl",
            "application/msexcel"                                                       => "xls",
            "application/x-msexcel"                                                     => "xls",
            "application/x-ms-excel"                                                    => "xls",
            "application/x-excel"                                                       => "xls",
            "application/x-dos_ms_excel"                                                => "xls",
            "application/xls"                                                           => "xls",
            "application/x-xls"                                                         => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"         => "xlsx",
            "application/vnd.ms-excel"                                                  => "xlsx",
            "application/xml"                                                           => "xml",
            "text/xml"                                                                  => "xml",
            "text/xsl"                                                                  => "xsl",
            "application/xspf+xml"                                                      => "xspf",
            "application/x-compress"                                                    => "z",
            "application/x-zip"                                                         => "zip",
            "application/zip"                                                           => "zip",
            "application/x-zip-compressed"                                              => "zip",
            "application/s-compressed"                                                  => "zip",
            "multipart/x-zip"                                                           => "zip",
            "text/x-scriptzsh"                                                          => "zsh",
        ];

        if (Strings::contains($mimeType, ";")) {
            $mimeType = Strings::substringBefore($mimeType, ";");
        }
        return isset($mimeMap[$mimeType]) ? $mimeMap[$mimeType] : "";
    }
}
