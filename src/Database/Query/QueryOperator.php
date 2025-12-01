<?php
namespace Framework\Database\Query;

/**
 * The Query Operators
 */
enum QueryOperator : string {

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
     * Creates an Query Operator from a String
     * @param QueryOperator|string $value
     * @return QueryOperator
     */
    public static function fromValue(QueryOperator|string $value): QueryOperator {
        if ($value instanceof QueryOperator) {
            return $value;
        }
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return self::Equal;
    }
}
