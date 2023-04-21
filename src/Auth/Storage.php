<?php
namespace Framework\Auth;

use Framework\Auth\Auth;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;

/**
 * The Storage class
 */
class Storage {

    private Schema $schema;
    private string $bucket = "";

    /** @var array{}[] */
    private array  $data   = [];


    /**
     * Creates a new Storage instance
     * @param string  $bucket
     * @param boolean $forAPI Optional.
     */
    public function __construct(string $bucket, bool $forAPI = false) {
        $this->schema = Factory::getSchema("storage");
        $this->bucket = $bucket;

        $query = Query::create("bucket", "=", $bucket);
        $query->addIf("CREDENTIAL_ID", "=", Auth::getID(), $forAPI);
        $data  = $this->schema->getValue($query, "data");

        if (!empty($data)) {
            $this->data = JSON::decode($data, true);
            $this->schema->edit($query, [
                "time" => time(),
            ]);
        }
    }



    /**
     * Returns the request data at the given key
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed {
        return $this->get($key);
    }

    /**
     * Sets the given data at the given key
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value): void {
        $this->set($key, $value);
    }

    /**
     * Returns true if the given key is set
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key): bool {
        return $this->exists($key);
    }

    /**
     * Removes the data at the given key
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void {
        $this->remove($key);
    }



    /**
     * Returns the data at the given key or the default
     * @param string       $key
     * @param mixed|string $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = ""): mixed {
        if ($this->exists($key)) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     * Sets the given data at the given key
     * @param string       $key
     * @param mixed|string $value Optional.
     * @return boolean
     */
    public function set(string $key, mixed $value = ""): bool {
        $this->data[$key] = $value;
        return $this->save();
    }

    /**
     * Sets multiple values at once
     * @param array{}[] $array
     * @return boolean
     */
    public function setArray(array $array): bool {
        foreach ($array as $key => $value) {
            $this->data[$key] = $value;
        }
        return $this->save();
    }



    /**
     * Removes the data at the given key
     * @param string $key
     * @return boolean
     */
    public function remove(string $key): bool {
        if ($this->exists($key)) {
            unset($this->data[$key]);
        }
        return $this->save();
    }

    /**
     * Removes the data at all the given keys
     * @param string[] $keys
     * @return boolean
     */
    public function removeAll(array $keys): bool {
        foreach ($keys as $key) {
            if ($this->exists($key)) {
                unset($this->data[$key]);
            }
        }
        return $this->save();
    }



    /**
     * Returns true if the given key has data
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        return !empty($this->data[$key]);
    }

    /**
     * Returns true if all the given keys are not empty
     * @param string[] $keys
     * @return boolean
     */
    public function hasAll(array $keys): bool {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the given key is set
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool {
        return isset($this->data[$key]);
    }



    /**
     * Returns all the data as an array
     * @return array{}[]
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * Removes all the data
     * @return boolean
     */
    public function clear(): bool {
        $query = Query::create("bucket", "=", $this->bucket);
        $query->add("CREDENTIAL_ID", "=", Auth::getID());
        if (!$this->schema->remove($query)) {
            return false;
        }
        $this->data = [];
        return true;
    }

    /**
     * Saves the data
     * @return boolean
     */
    private function save(): bool {
        if (empty($this->data)) {
            $this->clear();
            return false;
        }
        $this->schema->replace([
            "CREDENTIAL_ID" => Auth::getID(),
            "bucket"        => $this->bucket,
            "data"          => JSON::encode($this->data),
            "time"          => time(),
        ]);
        return true;
    }

    /**
     * Deletes the old storage data for all the Credentials
     * @return boolean
     */
    public static function deleteOld(): bool {
        $schema = Factory::getSchema("storage");
        if (empty($schema)) {
            return false;
        }
        $query  = Query::create("time", "<", DateTime::getLastXDays(1));
        return $schema->remove($query);
    }
}
