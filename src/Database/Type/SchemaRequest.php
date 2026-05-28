<?php
namespace Framework\Database\Type;

use Framework\IO\Request;
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
    public function __construct(SchemaRequest|Request|null $request = null) {
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
        return $this->request->toDictionary();
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
}
