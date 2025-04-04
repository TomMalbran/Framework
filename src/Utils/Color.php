<?php
namespace Framework\Utils;

/**
 * The Colors
 */
enum Color : string {

    case None         = "#f2f2f2";
    case Red          = "#e8384f";
    case Orange       = "#fd612c";
    case YellowOrange = "#fd9a00";
    case Yellow       = "#eec300";
    case YellowGreen  = "#a4cf30";
    case Green        = "#62d26f";
    case BlueGreen    = "#37c5ab";
    case Aqua         = "#20aaea";
    case Blue         = "#4186e0";
    case Indigo       = "#7a6ff0";
    case Purple       = "#aa62e3";
    case Magenta      = "#e362e3";
    case HotPink      = "#ea4e9d";
    case Pink         = "#fc91ad";
    case Gray         = "#8da3a6";



    /**
     * Returns true if the Color is valid
     * @param string $value
     * @return boolean
     */
    public static function isValid(string $value): bool {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a list of Colors
     * @return string[]
     */
    public static function getValues(): array {
        $list = [];
        foreach (self::cases() as $case) {
            $list[] = $case->value;
        }
        return $list;
    }
}
