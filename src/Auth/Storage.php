<?php
namespace Framework\Auth;

use Framework\Auth\Auth;
use Framework\Schema\Factory;
use Framework\Schema\Query;
use Framework\Utils\JSON;

/**
 * The Storage class
 */
class Storage {

    private $schema;
    private $data   = [];
    private $bucket = "";


    /**
     * Creates a new Storage instance
     * @param string $bucket
     */
    public function __construct(string $bucket) {
        $this->schema = Factory::getSchema("storage");
        $this->bucket = $bucket;

        $query = Query::create("bucket", "=", $bucket);
        $query->add("CREDENTIAL_ID", "=", Auth::getID());
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
    public function __get(string $key) {
        return $this->get($key);
    }

    /**
     * Sets the given data at the given key
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, $value): void {
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
     * @param string $key
     * @param mixed  $default Optional.
     * @return mixed
     */
    public function get(string $key, $default = "") {
        if ($this->exists($key)) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     * Sets the given data at the given key
     * @param string $key
     * @param mixed  $value Optional.
     * @return void
     */
    public function set(string $key, $value = ""): void {
        $this->data[$key] = $value;
        $this->save();
    }

    /**
     * Sets multiple values at once
     * @param array $array
     * @return void
     */
    public function setArray(array $array): void {
        foreach ($array as $key => $value) {
            $this->data[$key] = $value;
        }
        $this->save();
    }



    /**
     * Removes the data at the given key
     * @param string $key
     * @return void
     */
    public function remove(string $key): void {
        if ($this->exists($key)) {
            unset($this->data[$key]);
        }
        $this->save();
    }

    /**
     * Removes the data at all the given keys
     * @param string[] $keys
     * @return void
     */
    public function removeAll(array $keys): void {
        foreach ($keys as $key) {
            if ($this->exists($key)) {
                unset($this->data[$key]);
            }
        }
        $this->save();
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
     * @return array
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * Removes all the data
     * @return void
     */
    public function clear(): void {
        $query = Query::create("bucket", "=", $this->bucket);
        $query->add("CREDENTIAL_ID", "=", Auth::getID());
        $this->schema->remove($query);
        $this->data = [];
    }

    /**
     * Saves the data
     * @return void
     */
    private function save(): void {
        if (empty($this->data)) {
            $this->clear();
            return;
        }
        $this->schema->replace([
            "CREDENTIAL_ID" => Auth::getID(),
            "bucket"        => $this->bucket,
            "data"          => JSON::encode($this->data),
            "time"          => time(),
        ]);
    }

    /**
     * Deletes the old storage data for all the Credentials
     * @return void
     */
    public static function deleteOld(): void {
        $schema = Factory::getSchema("storage");
        if (!empty($schema)) {
            $query  = Query::create("time", "<", time() - 24 * 3600);
            $schema->remove($query);
        }
    }
}
