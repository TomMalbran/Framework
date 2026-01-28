<?php
namespace Framework\Date;

use Framework\Request;
use Framework\Date\Date;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Select;

/**
 * The Period Types used by the System
 */
class Period {

    public const Today         = "today";
    public const Yesterday     = "yesterday";
    public const Tomorrow      = "tomorrow";

    public const Last7Days     = "last7Days";
    public const Last15Days    = "last15Days";
    public const Last30Days    = "last30Days";
    public const Last60Days    = "last60Days";
    public const Last90Days    = "last90Days";
    public const Last120Days   = "last120Days";
    public const LastYear      = "lastYear";

    public const ThisWeek      = "thisWeek";
    public const ThisMonth     = "thisMonth";
    public const ThisYear      = "thisYear";

    public const PastWeek      = "pastWeek";
    public const PastMonth     = "pastMonth";
    public const PastYear      = "pastYear";

    public const AllPeriod     = "allPeriod";
    public const Custom        = "custom";


    public string $period   = "";
    public int    $fromTime = 0;
    public int    $toTime   = 0;


    /** @var array<string,string> All the Period Names */
    public static array $names = [
        self::Today         => "Hoy",
        self::Yesterday     => "Ayer",
        self::Tomorrow      => "Mañana",

        self::Last7Days     => "Últimos 7 días",
        self::Last15Days    => "Últimos 15 días",
        self::Last30Days    => "Últimos 30 días",
        self::Last60Days    => "Últimos 60 días",
        self::Last90Days    => "Últimos 90 días",
        self::Last120Days   => "Últimos 120 días",
        self::LastYear      => "Último año",

        self::ThisWeek      => "Esta semana",
        self::ThisMonth     => "Este mes",
        self::ThisYear      => "Este año",

        self::PastWeek      => "La semana pasada",
        self::PastMonth     => "El mes pasado",
        self::PastYear      => "El año pasado",

        self::AllPeriod     => "Todo el periodo",
        self::Custom        => "Personalizado",
    ];



    /**
     * Creates a new Period instance
     * @param Request $request
     * @param string  $prefix  Optional.
     */
    public function __construct(Request $request, string $prefix = "") {
        $period   = $request->getString("period");
        $fromDate = $request->getString("fromDate");
        $fromHour = $request->getString("fromHour");
        $fromTime = $request->getInt("fromTime");
        $toDate   = $request->getString("toDate");
        $toHour   = $request->getString("toHour");
        $toTime   = $request->getInt("toTime");

        if ($prefix !== "") {
            $period   = $request->getString($prefix);
            $fromDate = $request->getString("{$prefix}FromDate");
            $fromHour = $request->getString("{$prefix}FromHour");
            $fromTime = $request->getInt("{$prefix}FromTime");
            $toDate   = $request->getString("{$prefix}ToDate");
            $toHour   = $request->getString("{$prefix}ToHour");
            $toTime   = $request->getInt("{$prefix}ToTime");
        }

        $this->period = self::Custom;

        if ($fromDate !== "" && $fromHour !== "") {
            $this->fromTime = Date::fromString($fromDate, $fromHour)->getTimeStamp();
        } elseif ($fromDate !== "") {
            $this->fromTime = Date::fromString($fromDate)->getTimeStamp();
        } elseif ($fromTime !== 0) {
            $this->fromTime = $fromTime;
        } elseif ($period !== "") {
            $this->period   = $period;
            $this->fromTime = $this->getFromTime();
        }

        if ($toDate !== "" && $toHour !== "") {
            $this->toTime = Date::fromString($toDate, $toHour)->getTimeStamp();
        } elseif ($toDate !== "") {
            $this->toTime = Date::fromString($toDate)->toDayEnd()->getTimeStamp();
        } elseif ($toTime !== 0) {
            $this->toTime = $toTime;
        } elseif ($period !== "") {
            $this->period = $period;
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
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->fromTime === 0 && $this->toTime === 0;
    }

    /**
     * Returns true if the Period is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return $this->fromTime !== 0 || $this->toTime !== 0;
    }

    /**
     * Returns the From Time depending on the period
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public function getFromTime(bool $useTimeZone = true): int {
        $now    = Date::now();
        $date   = $now->toDayStart();

        $result = match ($this->period) {
            self::Today         => $date,
            self::Yesterday     => $date->moveDay(-1),
            self::Tomorrow      => $date->moveDay(1),

            self::Last7Days     => $date->moveDay(-7),
            self::Last15Days    => $date->moveDay(-15),
            self::Last30Days    => $date->moveDay(-30),
            self::Last60Days    => $date->moveDay(-60),
            self::Last90Days    => $date->moveDay(-90),
            self::Last120Days   => $date->moveDay(-120),
            self::LastYear      => $date->moveYear(-1),

            self::ThisWeek      => $date->toWeekStart(),
            self::ThisMonth     => $date->toMonthStart(),
            self::ThisYear      => $date->toYearStart(),

            self::PastWeek      => $date->moveWeek(-1)->toWeekStart(),
            self::PastMonth     => $date->moveMonth(-1)->toMonthStart(),
            self::PastYear      => $date->moveYear(-1)->toYearStart(),

            self::AllPeriod     => new Date(),
            self::Custom        => new Date($this->fromTime),
            default             => new Date(),
        };

        return $result->toServerTime($useTimeZone);
    }

    /**
     * Returns the To Time depending on the period
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public function getToTime(bool $useTimeZone = true): int {
        $now    = Date::now();
        $date   = $now->toDayEnd();

        $result = match ($this->period) {
            self::Today         => $date,
            self::Yesterday     => $date->moveDay(-1),
            self::Tomorrow      => $date->moveDay(1),

            self::Last7Days,
            self::Last15Days,
            self::Last30Days,
            self::Last60Days,
            self::Last90Days,
            self::Last120Days,
            self::LastYear      => $date,

            self::ThisWeek      => $date->toWeekEnd(),
            self::ThisMonth     => $date->toMonthEnd(),
            self::ThisYear      => $date->toYearEnd(),

            self::PastWeek      => $date->moveWeek(-1)->toWeekEnd(),
            self::PastMonth     => $date->moveMonth(-1)->toMonthEnd(),
            self::PastYear      => $date->moveYear(-1)->toYearEnd(),

            self::AllPeriod     => $date,
            self::Custom        => new Date($this->toTime),
            default             => $date,
        };

        return $result->toServerTime($useTimeZone);
    }

    /**
     * Returns the amount of days in the Period
     * @return int
     */
    public function getDaysAmount(): int {
        return self::getDays($this->period);
    }



    /**
     * Returns true if the given value is valid
     * @param string $value
     * @return bool
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
        return isset(self::$names[$value]) ? self::$names[$value] : "";
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
     * @return int
     */
    public static function getDays(string $period): int {
        $date = Date::now();

        return match ($period) {
            self::Today         => 1,
            self::Yesterday     => 1,
            self::Tomorrow      => 1,

            self::Last7Days     => 7,
            self::Last15Days    => 15,
            self::Last30Days    => 30,
            self::Last60Days    => 60,
            self::Last90Days    => 90,
            self::Last120Days   => 120,
            self::LastYear      => 365,

            self::ThisWeek      => 7,
            self::ThisMonth     => $date->getMonthDays(),
            self::ThisYear      => $date->getYearDays(),

            self::PastWeek      => 7,
            self::PastMonth     => $date->moveMonth(-1)->getMonthDays(),
            self::PastYear      => $date->moveYear(-1)->getYearDays(),

            self::AllPeriod     => 0,
            default             => 0,
        };
    }
}
