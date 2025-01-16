<?php
namespace Framework\System;

use Framework\Framework;
use Framework\System\AccessCode;
use Framework\System\ConfigCode;
use Framework\System\SettingCode;
use Framework\System\SignalCode;
use Framework\System\StatusCode;
use Framework\System\RouterCode;
use Framework\File\File;
use Framework\File\FilePath;
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
        $writePath = Framework::getPath(Framework::SystemDir);
        File::createDir($writePath);
        File::emptyDir($writePath);

        self::generateOne("Access",  AccessCode::getCode());
        self::generateOne("Config",  ConfigCode::getCode());
        self::generateOne("Setting", SettingCode::getCode());
        self::generateOne("Signal",  SignalCode::getCode());
        self::generateOne("Status",  StatusCode::getCode());
        self::generateOne("Router",  RouterCode::getCode());
        self::generateOne("Path",    FilePath::getCode());

        return true;
    }

    /**
     * Generates a single System Code
     * @param string  $name
     * @param array{} $data
     * @return boolean
     */
    private static function generateOne(string $name, array $data): bool {
        if (empty($data)) {
            return false;
        }

        $writePath = Framework::getPath(Framework::SystemDir);
        $template  = Framework::loadFile(Framework::TemplateDir, "$name.mu");
        $contents  = Mustache::render($template, $data + [
            "nameSpace" => Framework::Namespace,
            "codeSpace" => Framework::Namespace . self::Namespace,
        ]);

        File::create($writePath, "$name.php", $contents);
        print("Generated the <i>$name</i> code<br>");
        return true;
    }
}
