<?php
namespace Framework\Provider\Type;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Select;

use JsonSerializable;

/**
 * The Curl Method
 */
enum CurlMethod implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case GET;
    case POST;
    case PUT;
    case PATCH;
    case DELETE;


    /**
     * Creates a Select for the Fetch Methods
     * @return list<Select>
     */
    public static function getSelect(): array {
        $result = [];
        foreach (self::getAll() as $case) {
            $result[] = new Select($case, $case);
        }
        return $result;
    }
}
