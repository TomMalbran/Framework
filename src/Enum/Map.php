<?php
namespace Framework\Enum;

use Framework\Enum\Enum;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use SplObjectStorage;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;

/**
 * A Map wrapper
 * @template TKey of Enum
 * @template TValue
 * @implements IteratorAggregate<TKey,TValue>
 */
class Map implements Countable, IteratorAggregate, JsonSerializable {

    /** @var SplObjectStorage<TKey,TValue> */
    private SplObjectStorage $data;


    /**
     * Creates a new Map
     */
    public function __construct() {
        $this->data = new SplObjectStorage();
    }



    /**
     * Returns true if the data is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return count($this->data) === 0;
    }

    /**
     * Returns true if the data is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return count($this->data) !== 0;
    }



    /**
     * Sets a value in the Map
     * @param TKey   $key
     * @param TValue $value
     * @return void
     */
    public function set(Enum $key, mixed $value): void {
        $this->data->attach($key, $value);
    }

    /**
     * Checks if the Map contains a key
     * @param TKey $key
     * @return bool
     */
    public function has(Enum $key): bool {
        return $this->data->contains($key);
    }

    /**
     * Gets the value of the given key
     * @param TKey $key
     * @return TValue|null
     */
    public function get(Enum $key): mixed {
        if ($this->data->contains($key)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Gets the value of the given key as an Integer
     * @param TKey $key
     * @return int
     */
    public function getInt(Enum $key): int {
        if ($this->has($key)) {
            return Numbers::toInt($this->get($key));
        }
        return 0;
    }

    /**
     * Gets the value of the given key as a String
     * @param TKey $key
     * @return string
     */
    public function getString(Enum $key): string {
        if ($this->has($key)) {
            return Strings::toString($this->get($key));
        }
        return "";
    }



    /**
     * Implements the Countable Interface
     * @phpstan-return 0|positive-int
     * @return int
     */
    #[\Override]
    public function count(): int {
        $result = count($this->data);
        return max(0, $result);
    }

    /**
     * Returns an Iterator
     * @return Generator<TKey,TValue>
     */
    #[\Override]
    public function getIterator(): Generator {
        foreach ($this->data as $enumKey) {
            yield $enumKey => $this->data->offsetGet($enumKey);
        }
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        $result = [];
        foreach ($this->data as $enumKey) {
            $result[$enumKey->toString()] = $this->data->offsetGet($enumKey);
        }
        return $result;
    }
}
