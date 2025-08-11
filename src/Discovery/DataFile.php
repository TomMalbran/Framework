<?php
namespace Framework\Discovery;

use Framework\Utils\Strings;

/**
 * The Data Files
 */
enum DataFile {

    case Migrations;
    case Access;
    case Files;
    case Settings;



    /**
     * Returns true if the given file is a DataFile
     * @param string $fileName
     * @return string
     */
    public static function getFileName(string $fileName): string {
        foreach (self::cases() as $case) {
            if (Strings::endsWith($fileName, $case->fileName())) {
                return $case->fileName();
            }
        }
        return "";
    }

    /**
     * Get the key of the column
     * @return string
     */
    public function name(): string {
        return Strings::lowerCaseFirst($this->name);
    }

    /**
     * Get the key of the column
     * @return string
     */
    public function fileName(): string {
        return $this->name() . ".json";
    }
}
