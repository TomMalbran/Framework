<?php
namespace Framework\Discovery;

use Framework\Utils\Strings;

/**
 * The Data Files
 */
enum DataFile {

    case Route;
    case Schemas;
    case Migrations;
    case Status;
    case Access;
    case Files;
    case Settings;



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
