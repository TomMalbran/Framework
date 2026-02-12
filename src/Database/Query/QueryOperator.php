<?php
namespace Framework\Database\Query;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Query Operators
 */
enum QueryOperator: string implements Enum, JsonSerializable {
    use IsEnum;

    case None           = "";

    case Equal          = "=";
    case NotEqual       = "<>";

    case In             = "IN";
    case NotIn          = "NOT IN";

    case GreaterThan    = ">";
    case LessThan       = "<";
    case GreaterOrEqual = ">=";
    case LessOrEqual    = "<=";

    case Like           = "LIKE";
    case NotLike        = "NOT LIKE";

    case StartsWith     = "STARTS";
    case NotStartsWith  = "NOT STARTS";
    case EndsWith       = "ENDS";
    case NotEndsWith    = "NOT ENDS";
}
