<?php
namespace Framework\System;

use Framework\Framework;
use Framework\System\ConfigCode;
use Framework\System\SettingCode;
use Framework\System\SignalCode;
use Framework\System\StatusCode;
use Framework\Provider\Mustache;
use Framework\File\File;

/**
 * The Code
 */
class Code {

    const Namespace = "System";



    /**
     * Generates all the System Codes
     * @return boolean
     */
    public static function generate(): bool {
        $writePath = Framework::getPath(Framework::SystemDir);
        File::createDir($writePath);
        File::emptyDir($writePath);

        self::generateOne("Config",  ConfigCode::getCode());
        self::generateOne("Setting", SettingCode::getCode());
        self::generateOne("Signal",  SignalCode::getCode());
        self::generateOne("Status",  StatusCode::getCode());

        print("<br>");
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
