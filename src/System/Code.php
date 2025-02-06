<?php
namespace Framework\System;

use Framework\Framework;
use Framework\System\AccessCode;
use Framework\System\ConfigCode;
use Framework\System\SettingCode;
use Framework\System\SignalCode;
use Framework\System\StatusCode;
use Framework\File\File;
use Framework\File\FilePath;
use Framework\Route\RouterCode;
use Framework\Provider\Mustache;

/**
 * The Code
 */
class Code {

    const Namespace = "System";



    /**
     * Generates all the System Codes
     * @return boolean
     */
    public static function generateCode(): bool {
        print("\nFRAMEWORK CODES\n");

        $writePath    = Framework::getPath(Framework::SystemDir);
        $internalPath = Framework::getPath(Framework::RouteDir, forFramework: true);

        File::createDir($writePath);
        File::emptyDir($writePath);

        self::generateOne($writePath, "Access",  AccessCode::getCode());
        self::generateOne($writePath, "Config",  ConfigCode::getCode());
        self::generateOne($writePath, "Setting", SettingCode::getCode());
        self::generateOne($writePath, "Signal",  SignalCode::getCode());
        self::generateOne($writePath, "Status",  StatusCode::getCode());
        self::generateOne($writePath, "Path",    FilePath::getCode());

        self::generateOne($internalPath, "Router", RouterCode::getCode());
        return true;
    }

    /**
     * Generates a single System Code
     * @param string  $writePath
     * @param string  $name
     * @param array{} $data
     * @return boolean
     */
    private static function generateOne(string $writePath, string $name, array $data): bool {
        if (empty($data)) {
            return false;
        }

        $template = Framework::loadFile(Framework::TemplateDir, "$name.mu");
        $contents = Mustache::render($template, $data + [
            "nameSpace" => Framework::Namespace,
            "codeSpace" => Framework::Namespace . self::Namespace,
        ]);

        File::create($writePath, "$name.php", $contents);
        print("- Generated the $name code\n");
        return true;
    }
}
