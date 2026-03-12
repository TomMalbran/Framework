<?php
namespace Framework\Database\Query;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Query Operators
 */
enum Operator: string implements Enum, JsonSerializable {
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



    /**
     * Converts the Operator to SQL
     * @return string
     */
    public function toSQL(): string {
        return match ($this) {
            self::None          => "",
            self::Like          => "LIKE",
            self::NotLike       => "NOT LIKE",
            self::StartsWith    => "LIKE",
            self::NotStartsWith => "NOT LIKE",
            self::EndsWith      => "LIKE",
            self::NotEndsWith   => "NOT LIKE",
            default             => $this->value,
        };
    }
}
