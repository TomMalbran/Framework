<?php
namespace Framework\Utils;

use Framework\Request;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;

/**
 * The Period Types used by the System
 */
class Period {

    const Today       = "today";
    const Yesterday   = "yesterday";
    const Last7Days   = "las7Days";
    const Last15Days  = "last15Days";
    const Last30Days  = "last30Days";
    const Last60Days  = "last60Days";
    const Last90Days  = "last90Days";
    const Last120Days = "last120Days";
    const LastYear    = "lastYear";
    const ThisWeek    = "thisWeek";
    const ThisMonth   = "thisMonth";
    const ThisYear    = "thisYear";
    const PastWeek    = "pastWeek";
    const PastMonth   = "pastMonth";
    const PastYear    = "pastYear";
    const AllPeriod   = "allPeriod";
    const Custom      = "custom";

    public string $period   = "";
    public int    $fromTime = 0;
    public int    $toTime   = 0;

    /** @var array{} All the Period Names */
    public static array $names = [
        self::Today       => "Hoy",
        self::Yesterday   => "Ayer",
        self::Last7Days   => "Últimos 7 días",
        self::Last15Days  => "Últimos 15 días",
        self::Last30Days  => "Últimos 30 días",
        self::Last60Days  => "Últimos 60 días",
        self::Last90Days  => "Últimos 90 días",
        self::Last120Days => "Últimos 120 días",
        self::LastYear    => "Último año",
        self::ThisWeek    => "Esta semana",
        self::ThisMonth   => "Este mes",
        self::ThisYear    => "Este año",
        self::PastWeek    => "La semana pasada",
        self::PastMonth   => "El mes pasado",
        self::PastYear    => "El año pasado",
        self::AllPeriod   => "Todo el periodo",
        self::Custom      => "Personalizado",
    ];



    /**
     * Creates a new Period instance
     * @param Request|Model $data
     */
    public function __construct(Request|Model $data) {
        $this->period = self::Custom;

        if ($data->has("fromDate")) {
            $this->fromTime = DateTime::toDayStart($data->fromDate);
        } elseif ($data->has("fromTime")) {
            $this->fromTime = $data->fromTime;
        } elseif ($data->has("period")) {
            $this->period   = $data->period;
            $this->fromTime = $this->getFromTime();
        }

        if ($data->has("toDate")) {
            $this->toTime = DateTime::toDayEnd($data->toDate);
        } elseif ($data->has("toTime")) {
            $this->toTime = $data->toTime;
        } elseif ($data->has("period")) {
            $this->period = $data->period;
            $this->toTime = $this->getToTime();
        }
    }

    /**
     * Returns the From Time depending on the period
     * @return integer
     */
    public function getFromTime(): int {
        $day    = date("j");
        $date   = date("N");
        $month  = date("n");
        $year   = date("Y");
        $result = 0;

        switch ($this->period) {
        case self::Today:
            $result = mktime(0, 0, 0, $month, $day, $year);
            break;
        case self::Yesterday:
            $result = mktime(0, 0, 0, $month, $day - 1, $year);
            break;
        case self::Last7Days:
            $result = mktime(0, 0, 0, $month, $day - 7, $year);
            break;
        case self::Last15Days:
            $result = mktime(0, 0, 0, $month, $day - 15, $year);
            break;
        case self::Last30Days:
            $result = mktime(0, 0, 0, $month, $day - 30, $year);
            break;
        case self::Last60Days:
            $result = mktime(0, 0, 0, $month, $day - 60, $year);
            break;
        case self::Last90Days:
            $result = mktime(0, 0, 0, $month, $day - 90, $year);
            break;
        case self::Last120Days:
            $result = mktime(0, 0, 0, $month, $day - 120, $year);
            break;
        case self::LastYear:
            $result = mktime(0, 0, 0, $month, $day, $year - 1);
            break;
        case self::ThisWeek:
            $result = mktime(0, 0, 0, $month, $day - $date, $year);
            break;
        case self::ThisMonth:
            $result = mktime(0, 0, 0, $month, 1, $year);
            break;
        case self::ThisYear:
            $result = mktime(0, 0, 0, 1, 1, $year);
            break;
        case self::PastWeek:
            $result = mktime(0, 0, 0, $month, $day - $date - 7, $year);
            break;
        case self::PastMonth:
            $result = mktime(0, 0, 0, $month - 1, 1, $year);
            break;
        case self::PastYear:
            $result = mktime(0, 0, 0, 1, 1, $year - 1);
            break;
        case self::AllPeriod:
        default:
        }

        return DateTime::toServerTime($result);
    }

    /**
     * Returns the To Time depending on the period
     * @return integer
     */
    public function getToTime(): int {
        $month  = date("n");
        $day    = date("j");
        $date   = date("N");
        $year   = date("Y");
        $days   = date("t", mktime(0, 0, 0, $month - 1, 1, $year));
        $result = 0;

        switch ($this->period) {
        case self::Today:
        case self::Last7Days:
        case self::Last15Days:
        case self::Last30Days:
        case self::Last60Days:
        case self::Last90Days:
        case self::Last120Days:
        case self::LastYear:
        case self::ThisWeek:
        case self::ThisMonth:
        case self::ThisYear:
            $result = mktime(23, 59, 59, $month, $day, $year);
            break;
        case self::Today:
            $result = mktime(23, 59, 59, $month, $day - 1, $year);
            break;
        case self::PastWeek:
            $result = mktime(23, 59, 59, $month, $day - $date - 1, $year);
            break;
        case self::PastMonth:
            $result = mktime(23, 59, 59, $month - 1, $days, $year);
            break;
        case self::PastYear:
            $days   = date("t", mktime(0, 0, 0, 12, 1, $year - 1));
            $result = mktime(23, 59, 59, 12, $days, $year - 1);
            break;
        case self::AllPeriod:
            $result = mktime(23, 59, 59, $month, $day, $year);
            break;
        default:
        }

        return DateTime::toServerTime($result);
    }



    /**
     * Returns true if the given value is valid
     * @param integer $value
     * @return boolean
     */
    public static function isValid(int $value): bool {
        return in_array($value, array_keys(self::$names));
    }

    /**
     * Returns the name at the given Period
     * @param integer $value
     * @return string
     */
    public static function getName(int $value): string {
        return !empty(self::$names[$value]) ? self::$names[$value] : "";
    }

    /**
     * Returns a select of Periods
     * @return array{}[]
     */
    public static function getSelect(): array {
        return Arrays::createSelectFromMap(self::$names);
    }



    /**
     * Returns the amount of days in the given Period
     * @param string $period
     * @return integer
     */
    public static function getDays(string $period): int {
        $month = date("n");
        $year  = date("Y");

        switch ($period) {
        case self::Today:
            return 1;
        case self::Yesterday:
            return 1;
        case self::Last7Days:
            return 7;
        case self::Last15Days:
            return 15;
        case self::Last30Days:
            return 30;
        case self::Last60Days:
            return 60;
        case self::Last90Days:
            return 90;
        case self::Last120Days:
            return 120;
        case self::LastYear:
            return 365;
        case self::ThisWeek:
            return 7;
        case self::ThisMonth:
            return (int)date("t");
        case self::ThisYear:
            return 365 + (int)date("L");
        case self::PastWeek:
            return 7;
        case self::PastMonth:
            return (int)date("t", mktime(0, 0, 0, $month - 1, 1, $year));
        case self::PastYear:
            return 365 + (int)date("L", mktime(0, 0, 0, 1, 1, $year - 1));
        case self::AllPeriod:
        default:
            return 0;
        }
    }
}
