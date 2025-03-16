<?php
namespace Framework\Utils;

use Framework\Request;
use Framework\Utils\DateTime;
use Framework\Utils\Dictionary;

/**
 * The Period Types used by the System
 */
class Period {

    const Today       = "today";
    const Yesterday   = "yesterday";
    const Last7Days   = "last7Days";
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
        $day    = (int)date("j");
        $date   = (int)date("N");
        $month  = (int)date("n");
        $year   = (int)date("Y");

        $result = match ($this->period) {
            self::Today       => mktime(0, 0, 0, $month, $day, $year),
            self::Yesterday   => mktime(0, 0, 0, $month, $day - 1, $year),
            self::Last7Days   => mktime(0, 0, 0, $month, $day - 7, $year),
            self::Last15Days  => mktime(0, 0, 0, $month, $day - 15, $year),
            self::Last30Days  => mktime(0, 0, 0, $month, $day - 30, $year),
            self::Last60Days  => mktime(0, 0, 0, $month, $day - 60, $year),
            self::Last90Days  => mktime(0, 0, 0, $month, $day - 90, $year),
            self::Last120Days => mktime(0, 0, 0, $month, $day - 120, $year),
            self::LastYear    => mktime(0, 0, 0, $month, $day, $year - 1),
            self::ThisWeek    => mktime(0, 0, 0, $month, $day - $date, $year),
            self::ThisMonth   => mktime(0, 0, 0, $month, 1, $year),
            self::ThisYear    => mktime(0, 0, 0, 1, 1, $year),
            self::PastWeek    => mktime(0, 0, 0, $month, $day - $date - 7, $year),
            self::PastMonth   => mktime(0, 0, 0, $month - 1, 1, $year),
            self::PastYear    => mktime(0, 0, 0, 1, 1, $year - 1),
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
        $month  = (int)date("n");
        $day    = (int)date("j");
        $date   = (int)date("N");
        $year   = (int)date("Y");
        $days   = (int)date("t", mktime(0, 0, 0, $month - 1, 1, $year));
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
        case self::Yesterday:
            $result = mktime(23, 59, 59, $month, $day - 1, $year);
            break;
        case self::PastWeek:
            $result = mktime(23, 59, 59, $month, $day - $date - 1, $year);
            break;
        case self::PastMonth:
            $result = mktime(23, 59, 59, $month - 1, $days, $year);
            break;
        case self::PastYear:
            $days   = (int)date("t", mktime(0, 0, 0, 12, 1, $year - 1));
            $result = mktime(23, 59, 59, 12, $days, $year - 1);
            break;
        case self::AllPeriod:
            $result = mktime(23, 59, 59, $month, $day, $year);
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
     * @param integer $value
     * @return boolean
     */
    public static function isValid(int $value): bool {
        return in_array($value, array_keys(self::$names));
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
        $month = (int)date("n");
        $year  = (int)date("Y");

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
            self::ThisMonth   => (int)date("t"),
            self::ThisYear    => 365 + (int)date("L"),
            self::PastWeek    => 7,
            self::PastMonth   => (int)date("t", mktime(0, 0, 0, $month - 1, 1, $year)),
            self::PastYear    => 365 + (int)date("L", mktime(0, 0, 0, 1, 1, $year - 1)),
            self::AllPeriod   => 0,
            default           => 0,
        };
    }
}
