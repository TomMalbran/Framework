<?php
namespace Framework\Database;

use Framework\Request;
use Framework\File\FilePath;
use Framework\Database\Assign;
use Framework\System\Config;
use Framework\System\Path;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Schema Field
 */
class Field {

    // The Types
    const ID       = "id";
    const Boolean  = "boolean";
    const Number   = "number";
    const Float    = "float";
    const Date     = "date";
    const String   = "string";
    const JSON     = "json";
    const HTML     = "html";
    const Text     = "text";
    const LongText = "longtext";
    const Encrypt  = "encrypt";
    const File     = "file";

    // The Data
    public string $key        = "";
    public string $type       = "";
    public int    $length     = 0;
    public int    $decimals   = 2;
    public string $dateType   = "middle";
    public string $date       = "";
    public string $hour       = "";
    public string $path       = "";
    public mixed  $default    = null;

    public bool   $isID       = false;
    public bool   $isPrimary  = false;
    public bool   $isKey      = false;
    public bool   $isName     = false;
    public bool   $isUnique   = false;
    public bool   $isParent   = false;
    public bool   $noExists   = false;
    public bool   $noEmpty    = false;
    public bool   $isSigned   = false;
    public bool   $noPrefix   = false;
    public bool   $canEdit    = false;

    public string $mergeTo    = "";
    public string $defaultTo  = "";

    public bool   $hasName    = false;
    public string $name       = "";
    public string $prefix     = "";
    public string $prefixName = "";


    /**
     * Creates a new Field instance
     * @param string              $key
     * @param array<string,mixed> $data
     * @param string              $prefix Optional.
     */
    public function __construct(string $key, array $data, string $prefix = "") {
        $this->key        = $key;

        $this->type       = !empty($data["type"])     ? $data["type"]          : self::String;
        $this->length     = !empty($data["length"])   ? (int)$data["length"]   : $this->length;
        $this->decimals   = !empty($data["decimals"]) ? (int)$data["decimals"] : $this->decimals;
        $this->dateType   = !empty($data["dateType"]) ? $data["dateType"]      : $this->dateType;
        $this->date       = !empty($data["date"])     ? $data["date"]          : $this->date;
        $this->hour       = !empty($data["hour"])     ? $data["hour"]          : $this->hour;
        $this->path       = !empty($data["path"])     ? $data["path"]          : $this->path;
        $this->default    = isset($data["default"])   ? $data["default"]       : $this->default;

        $this->isID       = $this->type === self::ID;
        $this->isPrimary  = !empty($data["isPrimary"]);
        $this->isKey      = !empty($data["isKey"]);
        $this->isName     = !empty($data["isName"]);
        $this->isUnique   = !empty($data["isUnique"]);
        $this->isParent   = !empty($data["isParent"]);
        $this->noExists   = !empty($data["noExists"]);
        $this->noEmpty    = !empty($data["noEmpty"]);
        $this->isSigned   = !empty($data["isSigned"]);
        $this->noPrefix   = !empty($data["noPrefix"]);
        $this->canEdit    = empty($data["cantEdit"]);

        $this->mergeTo    = !empty($data["mergeTo"])   ? $data["mergeTo"]   : "";
        $this->defaultTo  = !empty($data["defaultTo"]) ? $data["defaultTo"] : "";

        $this->hasName    = Strings::isUpperCase($key);
        $this->name       = $this->getFieldName();
        $this->prefix     = $prefix;
        $this->prefixName = $this->getFieldKey();
    }

    /**
     * Returns a Field Name transforming it if is Upper Case
     * @return string
     */
    private function getFieldName(): string {
        $result = $this->key;
        if ($this->hasName) {
            $result = Strings::upperCaseToCamelCase($this->key);
            $result = Strings::replaceEnd($result, "Id", "ID");
        }
        return $result;
    }

    /**
     * Returns a Field Key with a Prefix
     * @return string
     */
    private function getFieldKey(): string {
        $result = $this->name;
        if (!empty($this->prefix) && !$this->noPrefix) {
            $result = $this->prefix . ucfirst($this->name);
        }
        return $result;
    }



    /**
     * Returns the Field Type from the Data
     * @param boolean $withLength Optional.
     * @return string
     */
    public function getType(bool $withLength = true): string {
        $type       = "unknown";
        $length     = 0;
        $attributes = "";
        $default    = null;

        switch ($this->type) {
        case self::ID:
            $type       = "int";
            $length     = 10;
            $attributes = "unsigned NOT NULL AUTO_INCREMENT";
            break;
        case self::Boolean:
            $type       = "tinyint";
            $length     = 1;
            $attributes = "unsigned NOT NULL";
            $default    = 0;
            break;
        case self::Float:
            $type       = "bigint";
            $length     = 20;
            $attributes = $this->isSigned ? "NOT NULL" : "unsigned NOT NULL";
            $default    = 0;
            break;
        case self::Number:
        case self::Date:
            $type       = "int";
            $length     = $this->length ?: 10;
            $attributes = $this->isSigned ? "NOT NULL" : "unsigned NOT NULL";
            $default    = 0;

            if ($length < 3) {
                $type = "tinyint";
            } elseif ($length < 5) {
                $type = "smallint";
            } elseif ($length < 8) {
                $type = "mediumint";
            } elseif ($length > 10) {
                $type = "bigint";
            }
            break;
        case self::String:
        case self::File:
            $type       = "varchar";
            $length     = $this->length ?: 255;
            $attributes = "NOT NULL";
            $default    = "";
            break;
        case self::JSON:
        case self::HTML:
            $type       = "mediumtext";
            $attributes = "NULL";
            break;
        case self::Text:
            $type       = "text";
            $attributes = "NULL";
            break;
        case self::LongText:
            $type       = "longtext";
            $attributes = "NULL";
            break;
        case self::Encrypt:
            $type       = "varbinary";
            $length     = $this->length ?: 255;
            $attributes = "NOT NULL";
            break;
        }

        $result = $type;
        if ($withLength && $length > 0) {
            $result = "{$type}({$length}) $attributes";
        } elseif (!empty($attributes)) {
            $result = "$type $attributes";
        }

        if ($result !== "unknown") {
            if ($this->default !== null) {
                $result .= " DEFAULT '{$this->default}'";
            } elseif ($default !== null) {
                $result .= " DEFAULT '{$default}'";
            }
        }
        return $result;
    }

    /**
     * Returns the Field Value from the given Request
     * @param Request $request
     * @return mixed
     */
    public function fromRequest(Request $request): mixed {
        $result = null;

        switch ($this->type) {
        case self::ID:
            break;
        case self::Boolean:
            $result = $request->toBinary($this->name);
            break;
        case self::String:
            $result = $request->getString($this->name);
            break;
        case self::Number:
            $result = $request->getInt($this->name);
            break;
        case self::Float:
            $result = $request->toInt($this->name, $this->decimals);
            break;
        case self::Date:
            if (!empty($this->date) && !empty($this->hour)) {
                $result = $request->toTimeHour($this->date, $this->hour, true);
            } elseif (!empty($this->date)) {
                $result = $request->toDay($this->date, $this->dateType, true);
            } elseif (Numbers::isValid($request->get($this->name))) {
                $result = $request->getInt($this->name);
            } elseif ($request->has("{$this->name}Date") && $request->has("{$this->name}Hour")) {
                $result = $request->toTimeHour("{$this->name}Date", "{$this->name}Hour", true);
            } elseif ($request->has("{$this->name}Date")) {
                $result = $request->toDay("{$this->name}Date", $this->dateType, true);
            } else {
                $result = $request->toDay($this->name, $this->dateType, true);
            }
            break;
        case self::JSON:
            $result = $request->toJSON($this->name);
            break;
        case self::Encrypt:
            $value  = $request->get($this->name);
            $result = Assign::encrypt($value, Config::getDbKey());
            break;
        default:
            $result = $request->get($this->name);
        }
        return $result;
    }

    /**
     * Returns the Field Values from the given Data
     * @param array<string,mixed> $data
     * @return array<string,string|integer|float|boolean|array<string|integer,mixed>>
     */
    public function toValues(array $data): array {
        $key    = $this->prefixName;
        $text   = isset($data[$key]) ? (string)$data[$key] : "";
        $number = isset($data[$key]) ? (int)$data[$key]    : 0;
        $result = [];

        switch ($this->type) {
        case self::Boolean:
            $result[$key]           = !empty($data[$key]);
            break;
        case self::ID:
        case self::Number:
            $result[$key]           = $number;
            break;
        case self::Float:
            $result[$key]           = Numbers::toFloat($number, $this->decimals);
            break;
        case self::Date:
            $result[$key]           = $number;
            $result["{$key}Date"]   = $number !== 0 ? date("d-m-Y",     $number) : "";
            $result["{$key}Full"]   = $number !== 0 ? date("d-m-Y H:i", $number) : "";
            break;
        case self::JSON:
            $result[$key]           = JSON::decodeAsArray($text);
            break;
        case self::HTML:
            $result[$key]           = $text;
            $result["{$key}Html"]   = Strings::toHtml($text);
            break;
        case self::Text:
        case self::LongText:
            $result[$key]           = $text;
            break;
        case self::Encrypt:
            $result[$key]           = !empty($data["{$key}Decrypt"]) ? $data["{$key}Decrypt"] : "";
            break;
        case self::File:
            $result[$key]           = $text;
            if (!empty($this->path)) {
                $result["{$key}Url"]   = $text !== "" ? FilePath::getUrl($this->path, $text) : "";
            } else {
                $result["{$key}Url"]   = $text !== "" ? Path::getSourceUrl("0", $text) : "";
                $result["{$key}Thumb"] = $text !== "" ? Path::getThumbsUrl("0", $text) : "";
            }
            break;
        default:
            $result[$key]           = $text;
        }
        return $result;
    }
}
