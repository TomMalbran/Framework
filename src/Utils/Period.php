<?php
namespace Framework\Utils;

use Framework\Request;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Dictionary;

/**
 * The Period Types used by the System
 */
class Period {

    public const Today       = "today";
    public const Yesterday   = "yesterday";
    public const Last7Days   = "last7Days";
    public const Last15Days  = "last15Days";
    public const Last30Days  = "last30Days";
    public const Last60Days  = "last60Days";
    public const Last90Days  = "last90Days";
    public const Last120Days = "last120Days";
    public const LastYear    = "lastYear";
    public const ThisWeek    = "thisWeek";
    public const ThisMonth   = "thisMonth";
    public const ThisYear    = "thisYear";
    public const PastWeek    = "pastWeek";
    public const PastMonth   = "pastMonth";
    public const PastYear    = "pastYear";
    public const AllPeriod   = "allPeriod";
    public const Custom      = "custom";


    public string $period   = "";
    public int    $fromTime = 0;
    public int    $toTime   = 0;

    /** @var array<string,string> All the Period Names */
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
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->period = self::Custom;

        if ($request->has("fromDate")) {
            $this->fromTime = DateTime::toDayStart($request->getString("fromDate"));
        } elseif ($request->has("fromTime")) {
            $this->fromTime = $request->getInt("fromTime");
        } elseif ($request->has("period")) {
            $this->period   = $request->getString("period");
            $this->fromTime = $this->getFromTime();
        }

        if ($request->has("toDate")) {
            $this->toTime = DateTime::toDayEnd($request->getString("toDate"));
        } elseif ($request->has("toTime")) {
            $this->toTime = $request->getInt("toTime");
        } elseif ($request->has("period")) {
            $this->period = $request->getString("period");
            $this->toTime = $this->getToTime();
        }
    }

    /**
     * Creates a new Period instance from the given Period
     * @param string $period
     * @return Period
     */
    public static function fromPeriod(string $period): Period {
        $data = new Request([ "period" => $period ]);
        return new Period($data);
    }

    /**
     * Creates a new Period instance from the given Period
     * @param Dictionary $data
     * @return Period
     */
    public static function fromDictionary(Dictionary $data): Period {
        $data = new Request([
            "period"   => $data->getString("period"),
            "fromDate" => $data->getString("fromDate"),
            "toDate"   => $data->getString("toDate"),
        ]);
        return new Period($data);
    }



    /**
     * Returns true if the Period is empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return $this->fromTime === 0 && $this->toTime === 0;
    }

    /**
     * Returns the From Time depending on the period
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public function getFromTime(bool $useTimeZone = true): int {
        $day    = DateTime::getDay();
        $date   = DateTime::getDayOfWeek(startMonday: true);
        $month  = DateTime::getMonth();
        $year   = DateTime::getYear();

        $result = match ($this->period) {
            self::Today       => DateTime::createTime($day,             $month,     $year),
            self::Yesterday   => DateTime::createTime($day - 1,         $month,     $year),
            self::Last7Days   => DateTime::createTime($day - 7,         $month,     $year),
            self::Last15Days  => DateTime::createTime($day - 15,        $month,     $year),
            self::Last30Days  => DateTime::createTime($day - 30,        $month,     $year),
            self::Last60Days  => DateTime::createTime($day - 60,        $month,     $year),
            self::Last90Days  => DateTime::createTime($day - 90,        $month,     $year),
            self::Last120Days => DateTime::createTime($day - 120,       $month,     $year),
            self::LastYear    => DateTime::createTime($day,             $month,     $year - 1),
            self::ThisWeek    => DateTime::createTime($day - $date,     $month,     $year),
            self::ThisMonth   => DateTime::createTime(1,                $month,     $year),
            self::ThisYear    => DateTime::createTime(1,                1,          $year),
            self::PastWeek    => DateTime::createTime($day - $date - 7, $month,     $year),
            self::PastMonth   => DateTime::createTime(1,                $month - 1, $year),
            self::PastYear    => DateTime::createTime(1,                1,          $year - 1),
            self::AllPeriod   => 0,
            default           => 0,
        };

        return DateTime::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the To Time depending on the period
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public function getToTime(bool $useTimeZone = true): int {
        $month  = DateTime::getMonth();
        $day    = DateTime::getDay();
        $date   = DateTime::getDayOfWeek(startMonday: true);
        $year   = DateTime::getYear();
        $days   = DateTime::getMonthDays(DateTime::createTime(1, $month - 1, $year));
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
            $result = DateTime::createTime($day, $month, $year, 23, 59, 59);
            break;
        case self::Yesterday:
            $result = DateTime::createTime($day - 1, $month, $year, 23, 59, 59);
            break;
        case self::PastWeek:
            $result = DateTime::createTime($day - $date - 1, $month, $year, 23, 59, 59);
            break;
        case self::PastMonth:
            $result = DateTime::createTime($days, $month - 1, $year, 23, 59, 59);
            break;
        case self::PastYear:
            $days   = DateTime::getMonthDays(DateTime::createTime(1, 12, $year - 1));
            $result = DateTime::createTime($days, 12, $year - 1, 23, 59, 59);
            break;
        case self::AllPeriod:
            $result = DateTime::createTime($day, $month, $year, 23, 59, 59);
            break;
        default:
        }

        return DateTime::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the amount of days in the Period
     * @return integer
     */
    public function getDaysAmount(): int {
        return self::getDays($this->period);
    }



    /**
     * Returns true if the given value is valid
     * @param string $value
     * @return boolean
     */
    public static function isValid(string $value): bool {
        return Arrays::containsKey(self::$names, $value);
    }

    /**
     * Returns the name at the given Period
     * @param string $value
     * @return string
     */
    public static function getName(string $value): string {
        return !empty(self::$names[$value]) ? self::$names[$value] : "";
    }

    /**
     * Returns a select of Periods
     * @return Select[]
     */
    public static function getSelect(): array {
        return Select::createFromMap(self::$names);
    }



    /**
     * Returns the amount of days in the given Period
     * @param string $period
     * @return integer
     */
    public static function getDays(string $period): int {
        $month = DateTime::getMonth();
        $year  = DateTime::getYear();

        return match ($period) {
            self::Today       => 1,
            self::Yesterday   => 1,
            self::Last7Days   => 7,
            self::Last15Days  => 15,
            self::Last30Days  => 30,
            self::Last60Days  => 60,
            self::Last90Days  => 90,
            self::Last120Days => 120,
            self::LastYear    => 365,
            self::ThisWeek    => 7,
            self::ThisMonth   => DateTime::getMonthDays(),
            self::ThisYear    => DateTime::getYearDays(),
            self::PastWeek    => 7,
            self::PastMonth   => DateTime::getMonthDays(DateTime::createTime(1, $month - 1, $year, 0, 0, 0)),
            self::PastYear    => DateTime::getYearDays(DateTime::createTime(1, 1, $year - 1)),
            self::AllPeriod   => 0,
            default           => 0,
        };
    }
}
