<?php
namespace Framework\Date;

/**
 * The Date Formats used by the System
 */
enum DateFormat : string {

    case Time           = "H:i";

    case Dashes         = "d-m-Y";
    case DashesReverse  = "Y-m-d";
    case DashesTime     = "d-m-Y H:i";
    case DashesSeconds  = "d-m-Y H:i:s";

    case Slashes        = "d/m/Y";
    case SlashesTime    = "d/m/Y H:i";
    case SlashesSeconds = "d/m/Y H:i:s";
}
