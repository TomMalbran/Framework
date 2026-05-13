<?php
namespace Framework\Date\Type;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Period Types used by the System
 */
enum PeriodType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Today;
    case Yesterday;
    case PrevYesterday;
    case Tomorrow;
    case NextTomorrow;

    case Last7Days;
    case Last15Days;
    case Last30Days;
    case Last60Days;
    case Last90Days;
    case Last120Days;
    case LastYear;

    case ThisWeek;
    case ThisMonth;
    case ThisYear;

    case PastWeek;
    case PastMonth;
    case PastYear;

    case NextWeek;
    case NextMonth;
    case NextYear;

    case AllPeriod;
    case Custom;



    /**
     * Returns a Name of the Enum
     * @return string
     */
    public function getName(): string {
        return Strings::lowerCaseFirst($this->name);
    }
}
