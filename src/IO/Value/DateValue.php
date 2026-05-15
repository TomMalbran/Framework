<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Date\Date;
use Framework\Date\DateUtils;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;

use JsonSerializable;

/**
 * The Date Value
 * @implements ValueInterface<Date,Date>
 */
class DateValue extends Value implements ValueInterface, JsonSerializable {

    private Date $value;
    private string $date;
    private string $hour;


    /**
     * Creates a new DateValue instance
     * @param Request  $request
     * @param string   $dateKey
     * @param string   $hourKey
     * @param DateType $dateType Optional.
     */
    public function __construct(
        Request $request,
        string $dateKey,
        string $hourKey,
        DateType $dateType = DateType::None,
    ) {
        parent::__construct($request, $dateKey);
        if ($hourKey !== "") {
            $this->value = $request->toTimeHour($dateKey, $hourKey);
        } elseif ($dateType !== DateType::None) {
            $this->value = $request->toDayMoment($dateKey, $dateType);
        } else {
            $this->value = $request->toDate($dateKey);
        }

        $this->date = $request->getString($dateKey);
        $this->hour = $request->getString($hourKey);
    }

    /**
     * Sets the value
     * @param DateValue|Date $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        if ($value instanceof DateValue) {
            $value = $value->get();
        }
        $this->value = $value;
        $this->setRaw($value->toString(DateFormat::Reverse));
    }

    /**
     * Sets the value if the value is not empty
     * @param DateValue|Date $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value instanceof DateValue) {
            $value = $value->get();
        }
        if ($value->isNotEmpty()) {
            $this->set($value);
        }
    }

    /**
    * Unsets the value
    * @return void
    */
    #[\Override]
    public function unset(): void {
        $this->set(Date::empty());
    }



    /**
     * Returns the value
     * @return Date
     */
    #[\Override]
    public function get(): Date {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is empty
     * @return Date|null
     */
    #[\Override]
    public function getOrNull(): ?Date {
        return $this->hasValue() ? $this->value : null;
    }

    /**
     * Returns the value for database storage
     * @return int
     */
    #[\Override]
    public function toDatabase(): int {
        return $this->value->toTime();
    }

    /**
     * Returns a Date instance as a timestamp
     * @return int
     */
    public function toTime(): int {
        return $this->value->toTime();
    }

    /**
     * Returns a Date instance set to the start of the day
     * @return Date
     */
    public function toDayStart(): Date {
        return $this->value->toDayStart();
    }

    /**
     * Returns a Date instance set to the middle of the day
     * @return Date
     */
    public function toDayMiddle(): Date {
        return $this->value->toDayMiddle();
    }

    /**
     * Returns a Date instance set to the end of the day
     * @return Date
     */
    public function toDayEnd(): Date {
        return $this->value->toDayEnd();
    }



    /**
     * Returns true if the value is valid
     * @return bool
     */
    public function isValid(): bool {
        return DateUtils::isValidDate($this->raw);
    }

    /**
     * Returns true if the period between this date and the end date is valid
     * @param DateValue $endDate
     * @return bool
     */
    public function isValidPeriod(DateValue $endDate): bool {
        if (!$this->hasValue() || !$endDate->hasValue()) {
            return true;
        }
        return DateUtils::isValidPeriod($this->date, $endDate->date);
    }

    /**
     * Returns true if the date at the given key is in the Future
     * @param DateType $dateType Optional.
     * @return bool
     */
    public function isFutureDate(DateType $dateType = DateType::Middle): bool {
        return $this->value->toDayMoment($dateType)->isFuture();
    }



    /**
     * Returns true if the hour is not empty
     * @return bool
     */
    public function hasHour(): bool {
        return $this->hour !== "";
    }

    /**
     * Returns the hour
     * @return string
     */
    public function getHour(): string {
        return $this->hour;
    }

    /**
     * Returns true if the hour is valid
     * @param list<int>|null $minutes Optional.
     * @return bool
     */
    public function isValidHour(?array $minutes = null): bool {
        return DateUtils::isValidHour($this->hour, $minutes);
    }

    /**
     * Returns true if the period between this hour and the end hour is valid
     * @param DateValue $endDate
     * @return bool
     */
    public function isValidHourPeriod(DateValue $endDate): bool {
        if (!$this->hasHour() || !$endDate->hasHour()) {
            return true;
        }
        return DateUtils::isValidHourPeriod($this->hour, $endDate->hour);
    }

    /**
     * Returns true if the period between this date and the end date is valid, including the hour
     * @param DateValue $endDate
     * @return bool
     */
    public function isValidFullPeriod(DateValue $endDate): bool {
        if (!$this->hasValue() || !$this->hasHour() || !$endDate->hasValue() || !$endDate->hasHour()) {
            return true;
        }
        return DateUtils::isValidFullPeriod(
            $this->date,
            $this->hour,
            $endDate->date,
            $endDate->hour,
        );
    }



    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->value->toString(DateFormat::Reverse);
    }
}
