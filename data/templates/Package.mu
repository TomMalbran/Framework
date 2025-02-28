<?php
namespace {{namespace}};

use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Package
 */
class Package {

    public const Namespace   = "{{appNamespace}}\";
    public const AppDir      = "{{appDir}}";
    public const SourceDir   = "{{sourceDir}}";

    public const DataDir     = "{{dataDir}}";
    public const TemplateDir = "{{templateDir}}";
    public const IntFilesDir = "{{intFilesDir}}";

    public const StringsDir  = "nls/strings";
    public const EmailsDir   = "nls/emails";

    public const FilesDir    = "files";
    public const LogDir      = "logs";
    public const FTPDir      = "public_ftp";

}
