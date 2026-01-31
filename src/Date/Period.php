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
    public const PrevYesterday = "prevYesterday";
    public const Tomorrow      = "tomorrow";
    public const NextTomorrow  = "nextTomorrow";

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

    public const NextWeek      = "nextWeek";
    public const NextMonth     = "nextMonth";
    public const NextYear      = "nextYear";

    public const AllPeriod     = "allPeriod";
    public const Custom        = "custom";


    /** @var array<string,string> All the Period Names */
    public static array $names = [
        self::Today         => "Hoy",
        self::Yesterday     => "Ayer",
        self::PrevYesterday => "Anteayer",
        self::Tomorrow      => "Mañana",
        self::NextTomorrow  => "Pasado mañana",

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

        self::NextWeek      => "La próxima semana",
        self::NextMonth     => "El próximo mes",
        self::NextYear      => "El próximo año",

        self::AllPeriod     => "Todo el periodo",
        self::Custom        => "Personalizado",
    ];



    // Period Properties
    public string $period;
    public Date $fromTime;
    public Date $toTime;


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
            $this->fromTime = Date::create($fromDate, $fromHour);
        } elseif ($fromDate !== "") {
            $this->fromTime = Date::create($fromDate);
        } elseif ($fromTime !== 0) {
            $this->fromTime = Date::create($fromTime);
        } elseif ($period !== "") {
            $this->period   = $period;
            $this->fromTime = $this->getFromTime();
        } else {
            $this->fromTime = Date::empty();
        }

        if ($toDate !== "" && $toHour !== "") {
            $this->toTime = Date::create($toDate, $toHour);
        } elseif ($toDate !== "") {
            $this->toTime = Date::create($toDate)->toDayEnd();
        } elseif ($toTime !== 0) {
            $this->toTime = Date::create($toTime);
        } elseif ($period !== "") {
            $this->period = $period;
            $this->toTime = $this->getToTime();
        } else {
            $this->toTime = Date::empty();
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
        return $this->fromTime->isEmpty() && $this->toTime->isEmpty();
    }

    /**
     * Returns true if the Period is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return $this->fromTime->isNotEmpty() || $this->toTime->isNotEmpty();
    }

    /**
     * Returns the From Time depending on the period
     * @param bool $useTimeZone Optional.
     * @return Date
     */
    public function getFromTime(bool $useTimeZone = true): Date {
        $now    = Date::now();
        $date   = $now->toDayStart();

        $result = match ($this->period) {
            self::Today         => $date,
            self::Yesterday     => $date->subtract(days: 1),
            self::PrevYesterday => $date->subtract(days: 2),
            self::Tomorrow      => $date->add(days: 1),
            self::NextTomorrow  => $date->add(days: 2),

            self::Last7Days     => $date->subtract(days: 7),
            self::Last15Days    => $date->subtract(days: 15),
            self::Last30Days    => $date->subtract(days: 30),
            self::Last60Days    => $date->subtract(days: 60),
            self::Last90Days    => $date->subtract(days: 90),
            self::Last120Days   => $date->subtract(days: 120),
            self::LastYear      => $date->subtract(years: 1),

            self::ThisWeek      => $date->toWeekStart(),
            self::ThisMonth     => $date->toMonthStart(),
            self::ThisYear      => $date->toYearStart(),

            self::PastWeek      => $date->subtract(weeks: 1)->toWeekStart(),
            self::PastMonth     => $date->subtract(months: 1)->toMonthStart(),
            self::PastYear      => $date->subtract(years: 1)->toYearStart(),

            self::NextWeek      => $date->add(weeks: 1)->toWeekEnd(),
            self::NextMonth     => $date->add(months: 1)->toMonthEnd(),
            self::NextYear      => $date->add(years: 1)->toYearEnd(),

            self::AllPeriod     => Date::empty(),
            self::Custom        => Date::create($this->fromTime),
            default             => Date::empty(),
        };

        return $result->toServerTime($useTimeZone);
    }

    /**
     * Returns the To Time depending on the period
     * @param bool $useTimeZone Optional.
     * @return Date
     */
    public function getToTime(bool $useTimeZone = true): Date {
        $now    = Date::now();
        $date   = $now->toDayEnd();

        $result = match ($this->period) {
            self::Today         => $date,
            self::Yesterday     => $date->subtract(days: 1),
            self::PrevYesterday => $date->subtract(days: 2),
            self::Tomorrow      => $date->add(days: 1),
            self::NextTomorrow  => $date->add(days: 2),

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

            self::PastWeek      => $date->subtract(weeks: 1)->toWeekEnd(),
            self::PastMonth     => $date->subtract(months: 1)->toMonthEnd(),
            self::PastYear      => $date->subtract(years: 1)->toYearEnd(),

            self::NextWeek      => $date->add(weeks: 1)->toWeekEnd(),
            self::NextMonth     => $date->add(months: 1)->toMonthEnd(),
            self::NextYear      => $date->add(years: 1)->toYearEnd(),

            self::AllPeriod     => $date,
            self::Custom        => Date::create($this->toTime),
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
            self::PrevYesterday => 1,
            self::Tomorrow      => 1,
            self::NextTomorrow  => 1,

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
            self::PastMonth     => $date->subtract(months: 1)->getMonthDays(),
            self::PastYear      => $date->subtract(years: 1)->getYearDays(),

            self::NextWeek      => 7,
            self::NextMonth     => $date->add(months: 1)->getMonthDays(),
            self::NextYear      => $date->add(years: 1)->getYearDays(),

            self::AllPeriod     => 0,
            default             => 0,
        };
    }
}
