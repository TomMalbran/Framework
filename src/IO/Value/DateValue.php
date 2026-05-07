<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Date\Date;
use Framework\Date\DateUtils;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;

/**
 * The Date Value
 * @implements ValueInterface<Date,Date>
 */
class DateValue extends Value implements ValueInterface {

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
     * @param Date $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value->toString(DateFormat::Reverse));
    }

    /**
     * Sets the value if the value is not empty
     * @param Date $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
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
     * Returns true if the hour is not empty
     * @return bool
     */
    public function hasHour(): bool {
        return $this->hour !== "";
    }

    /**
     * Returns true if the hour is valid
     * @return bool
     */
    public function isValidHour(): bool {
        return DateUtils::isValidHour($this->hour);
    }

    /**
     * Returns true if the period between this date and the end date is valid, including the hour
     * @param DateValue $endDate
     * @return bool
     */
    public function isValidFullPeriod(DateValue $endDate): bool {
        if ($this->date === "" || $this->hour === "" || $endDate->date === "" || $endDate->hour === "") {
            return true;
        }
        return DateUtils::isValidFullPeriod(
            $this->date,
            $this->hour,
            $endDate->date,
            $endDate->hour,
        );
    }
}
