<?php
namespace Framework\Date\Type;

/**
 * The Date Formats used by the System
 */
enum DateFormat: string {

    case Time           = "H:i";

    case Dashes         = "d-m-Y";
    case DashesTime     = "d-m-Y H:i";
    case DashesSeconds  = "d-m-Y H:i:s";

    case Reverse        = "Y-m-d";
    case ReverseTime    = "Y-m-d H:i";
    case ReverseSeconds = "Y-m-d H:i:s";

    case Slashes        = "d/m/Y";
    case SlashesTime    = "d/m/Y H:i";
    case SlashesSeconds = "d/m/Y H:i:s";
}
