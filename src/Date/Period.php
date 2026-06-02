<?php
namespace Framework\Date;

use Framework\IO\Request;
use Framework\Database\Type\SchemaRequest;
use Framework\Date\Date;
use Framework\Date\Type\PeriodType;
use Framework\Utils\Dictionary;

use IteratorAggregate;
use Generator;

/**
 * The Period Types used by the System
 * @implements IteratorAggregate<Date>
 */
class Period implements IteratorAggregate {

    public PeriodType $period;
    public Date $fromTime;
    public Date $toTime;


    /**
     * Creates a new Period instance
     * @param SchemaRequest|Request $request
     * @param string                $prefix  Optional.
     */
    public function __construct(
        SchemaRequest|Request $request,
        string $prefix = "",
    ) {
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

        $this->period = PeriodType::Custom;

        if ($fromDate !== "" && $fromHour !== "") {
            $this->fromTime = Date::create($fromDate, $fromHour);
        } elseif ($fromDate !== "") {
            $this->fromTime = Date::create($fromDate);
        } elseif ($fromTime !== 0) {
            $this->fromTime = Date::create($fromTime);
        } elseif ($period !== "") {
            $this->period   = PeriodType::fromValue($period);
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
            $this->period = PeriodType::fromValue($period);
            $this->toTime = $this->getToTime();
        } else {
            $this->toTime = Date::empty();
        }
    }

    /**
     * Creates a new Period instance from the given Period
     * @param PeriodType|string $period
     * @return Period
     */
    public static function fromPeriod(PeriodType|string $period): Period {
        $period = $period instanceof PeriodType ? $period->toString() : $period;
        $data   = new Request([ "period" => $period ]);
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
     * Returns true if the given value is valid
     * @param PeriodType|string $value
     * @return bool
     */
    public static function isValid(PeriodType|string $value): bool {
        return PeriodType::isValid($value);
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
    private function getFromTime(bool $useTimeZone = true): Date {
        $now    = Date::now();
        $date   = $now->toDayStart();

        $result = match ($this->period) {
            PeriodType::Today         => $date,
            PeriodType::Yesterday     => $date->subtract(days: 1),
            PeriodType::PrevYesterday => $date->subtract(days: 2),
            PeriodType::Tomorrow      => $date->add(days: 1),
            PeriodType::NextTomorrow  => $date->add(days: 2),

            PeriodType::Last7Days     => $date->subtract(days: 7),
            PeriodType::Last15Days    => $date->subtract(days: 15),
            PeriodType::Last30Days    => $date->subtract(days: 30),
            PeriodType::Last60Days    => $date->subtract(days: 60),
            PeriodType::Last90Days    => $date->subtract(days: 90),
            PeriodType::Last120Days   => $date->subtract(days: 120),
            PeriodType::LastYear      => $date->subtract(years: 1),

            PeriodType::ThisWeek      => $date->toWeekStart(),
            PeriodType::ThisMonth     => $date->toMonthStart(),
            PeriodType::ThisYear      => $date->toYearStart(),

            PeriodType::PastWeek      => $date->subtract(weeks: 1)->toWeekStart(),
            PeriodType::PastMonth     => $date->subtract(months: 1)->toMonthStart(),
            PeriodType::PastYear      => $date->subtract(years: 1)->toYearStart(),

            PeriodType::NextWeek      => $date->add(weeks: 1)->toWeekStart(),
            PeriodType::NextMonth     => $date->add(months: 1)->toMonthStart(),
            PeriodType::NextYear      => $date->add(years: 1)->toYearStart(),

            PeriodType::AllPeriod     => Date::empty(),
            PeriodType::Custom        => $date,
            default                   => Date::empty(),
        };

        return $result->toServerTime($useTimeZone);
    }

    /**
     * Returns the To Time depending on the period
     * @param bool $useTimeZone Optional.
     * @return Date
     */
    private function getToTime(bool $useTimeZone = true): Date {
        $now    = Date::now();
        $date   = $now->toDayEnd();

        $result = match ($this->period) {
            PeriodType::Today         => $date,
            PeriodType::Yesterday     => $date->subtract(days: 1),
            PeriodType::PrevYesterday => $date->subtract(days: 2),
            PeriodType::Tomorrow      => $date->add(days: 1),
            PeriodType::NextTomorrow  => $date->add(days: 2),

            PeriodType::Last7Days,
            PeriodType::Last15Days,
            PeriodType::Last30Days,
            PeriodType::Last60Days,
            PeriodType::Last90Days,
            PeriodType::Last120Days,
            PeriodType::LastYear      => $date,

            PeriodType::ThisWeek      => $date->toWeekEnd(),
            PeriodType::ThisMonth     => $date->toMonthEnd(),
            PeriodType::ThisYear      => $date->toYearEnd(),

            PeriodType::PastWeek      => $date->subtract(weeks: 1)->toWeekEnd(),
            PeriodType::PastMonth     => $date->subtract(months: 1)->toMonthEnd(),
            PeriodType::PastYear      => $date->subtract(years: 1)->toYearEnd(),

            PeriodType::NextWeek      => $date->add(weeks: 1)->toWeekEnd(),
            PeriodType::NextMonth     => $date->add(months: 1)->toMonthEnd(),
            PeriodType::NextYear      => $date->add(years: 1)->toYearEnd(),

            PeriodType::AllPeriod     => $date,
            PeriodType::Custom        => $date,
            default                   => Date::empty(),
        };

        return $result->toServerTime($useTimeZone);
    }

    /**
     * Returns the amount of days in the Period
     * @return int
     */
    public function getDaysAmount(): int {
        $date = Date::now();

        return match ($this->period) {
            PeriodType::Today         => 1,
            PeriodType::Yesterday     => 1,
            PeriodType::PrevYesterday => 1,
            PeriodType::Tomorrow      => 1,
            PeriodType::NextTomorrow  => 1,

            PeriodType::Last7Days     => 7,
            PeriodType::Last15Days    => 15,
            PeriodType::Last30Days    => 30,
            PeriodType::Last60Days    => 60,
            PeriodType::Last90Days    => 90,
            PeriodType::Last120Days   => 120,
            PeriodType::LastYear      => 365,

            PeriodType::ThisWeek      => 7,
            PeriodType::ThisMonth     => $date->getMonthDays(),
            PeriodType::ThisYear      => $date->getYearDays(),

            PeriodType::PastWeek      => 7,
            PeriodType::PastMonth     => $date->subtract(months: 1)->getMonthDays(),
            PeriodType::PastYear      => $date->subtract(years: 1)->getYearDays(),

            PeriodType::NextWeek      => 7,
            PeriodType::NextMonth     => $date->add(months: 1)->getMonthDays(),
            PeriodType::NextYear      => $date->add(years: 1)->getYearDays(),

            PeriodType::AllPeriod     => 0,
            PeriodType::Custom        => 0,
            default                   => 0,
        };
    }



    /**
     * Returns an iterator for the Period
     * @return Generator<Date>
     */
    #[\Override]
    public function getIterator(): Generator {
        $time   = $this->fromTime;
        $toTime = $this->toTime;

        if ($time->isAfter($toTime) || $time->isEmpty() || $toTime->isEmpty()) {
            return;  // phpcs:ignore
        }

        while ($time->isBeforeOrEqual($toTime)) {
            yield Date::create($time);
            $time = $time->add(days: 1);
        }
    }
}
