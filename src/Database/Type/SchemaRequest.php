<?php
namespace Framework\Database\Type;

use Framework\IO\Request;
use Framework\Discovery\Discovery;
use Framework\File\File;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Schema Request
 */
class SchemaRequest {

    protected Request $request;


    // Sort and Pagination
    public string $orderBy = "";
    public bool $orderAsc = false;

    public int $page = -1;
    public int $amount = 0;



    /**
     * Creates a new SchemaRequest instance
     * @param SchemaRequest|Request|null $request Optional.
     */
    protected function __construct(SchemaRequest|Request|null $request = null) {
        if ($request instanceof SchemaRequest) {
            $this->request = $request->request;
        } else {
            $this->request = $request ?? new Request();
        }

        $this->orderBy  = $this->request->getString("orderBy");
        $this->orderAsc = $this->request->getBool("orderAsc");
        $this->page     = $this->request->getInt("page");
        $this->amount   = $this->request->getInt("amount");
    }

    /**
     * Returns a list of Properties
     * @return list<string>
     */
    public function getProperties(): array {
        return array_keys(Discovery::getProperties($this));
    }



    /**
     * Returns true if the Request is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return !$this->request->has();
    }

    /**
     * Returns true if the Request is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return $this->request->has();
    }



    /**
     * Returns the Data as a String
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        if (property_exists($this, $key)) {
            return Strings::toString($this->$key);
        }
        if ($this->request->exists($key)) {
            return $this->request->getString($key, $default);
        }
        return $default;
    }

    /**
     * Returns the Data as an Integer
     * @param string $key
     * @param int    $default Optional.
     * @return int
     */
    public function getInt(string $key, int $default = 0): int {
        if (property_exists($this, $key)) {
            return Numbers::toInt($this->$key);
        }
        if ($this->request->exists($key)) {
            return $this->request->getInt($key, $default);
        }
        return $default;
    }

    /**
     * Returns the Data as a Boolean
     * @param string $key
     * @param bool   $default Optional.
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool {
        if (property_exists($this, $key) && is_bool($this->$key)) {
            return $this->$key;
        }
        if ($this->request->exists($key)) {
            return $this->request->getBool($key);
        }
        return $default;
    }

    /**
     * Returns the Data as a File
     * @param string $key
     * @return File
     */
    public function getFile(string $key): File {
        if (property_exists($this, $key) && $this->$key instanceof File) {
            return $this->$key;
        }
        return new File($key);
    }

    /**
     * Returns the Data as a Dictionary
     * @param string $key
     * @return Dictionary
     */
    public function getDictionary(string $key): Dictionary {
        if (property_exists($this, $key) && $this->$key instanceof Dictionary) {
            return $this->$key;
        }
        if ($this->request->exists($key)) {
            return $this->request->getDictionary($key);
        }
        return new Dictionary();
    }



    /**
     * Returns the original Request
     * @return Request
     */
    public function getRequest(): Request {
        return $this->request;
    }

    /**
     * Converts the Request to a Dictionary
     * @return Dictionary
     */
    public function toDictionary(): Dictionary {
        $result = new Dictionary();
        foreach ($this->getProperties() as $property) {
            $result->set($property, $this->$property);
        }
        return $result;
    }

    /**
     * Returns the Data as an Array
     * @param array<string,mixed> $extraData Optional.
     * @return array<string,mixed>
     */
    public function toArray(array $extraData = []): array {
        $result = [];
        foreach ($this->getProperties() as $property) {
            if ($this->$property instanceof Request) {
                continue;
            }

            if ($this->$property instanceof File) {
                $result[$property] = $this->$property->getValue();
            } elseif ($this->$property instanceof Dictionary) {
                $result[$property] = $this->$property->toArray();
            } else {
                $result[$property] = $this->$property;
            }
        }
        return $result + $extraData;
    }
}
