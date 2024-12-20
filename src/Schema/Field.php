<?php
namespace Framework\Schema;

use Framework\Request;
use Framework\File\FilePath;
use Framework\Utils\Arrays;
use Framework\Utils\CSV;
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
    const Binary   = "binary";
    const Number   = "number";
    const Float    = "float";
    const Price    = "price";
    const Date     = "date";
    const Hour     = "hour";
    const String   = "string";
    const JSON     = "json";
    const CSV      = "csv";
    const HTML     = "html";
    const Text     = "text";
    const LongText = "longtext";
    const Encrypt  = "encrypt";
    const File     = "file";

    // The Data
    public string $key        = "";
    public string $type       = "";
    public int    $length     = 0;
    public int    $decimals   = 3;
    public string $dateType   = "";
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
     * @param string  $key
     * @param array{} $data
     * @param string  $prefix Optional.
     */
    public function __construct(string $key, array $data, string $prefix = "") {
        $this->key        = $key;

        $this->type       = !empty($data["type"])     ? $data["type"]          : self::String;
        $this->length     = !empty($data["length"])   ? (int)$data["length"]   : 0;
        $this->decimals   = !empty($data["decimals"]) ? (int)$data["decimals"] : 3;
        $this->dateType   = !empty($data["dateType"]) ? $data["dateType"]      : "middle";
        $this->date       = !empty($data["date"])     ? $data["date"]          : "";
        $this->hour       = !empty($data["hour"])     ? $data["hour"]          : "";
        $this->path       = !empty($data["path"])     ? $data["path"]          : "";
        $this->default    = isset($data["default"])   ? $data["default"]       : null;

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

        $this->hasName    = !empty($data["name"]);
        $this->name       = !empty($data["name"]) ? $data["name"] : $key;
        $this->prefix     = $prefix;
        $this->prefixName = $this->getFieldKey();
    }

    /**
     * Creates a Field with a Prefix
     * @return string
     */
    public function getFieldKey(): string {
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
        case self::Binary:
        case self::Boolean:
            $type       = "tinyint";
            $length     = 1;
            $attributes = "unsigned NOT NULL";
            $default    = 0;
            break;
        case self::Price:
            $type       = "bigint";
            $length     = 20;
            $attributes = $this->isSigned ? "NOT NULL" : "unsigned NOT NULL";
            $default    = 0;
            break;
        case self::Number:
        case self::Float:
        case self::Date:
        case self::Hour:
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
        case self::CSV:
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
     * @param string  $masterKey Optional.
     * @return mixed
     */
    public function fromRequest(Request $request, string $masterKey = ""): mixed {
        $result = null;

        switch ($this->type) {
        case self::ID:
            break;
        case self::Boolean:
        case self::Binary:
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
        case self::Price:
            $result = $request->toCents($this->name);
            break;
        case self::Date:
            if (!empty($this->date) && $request->has($this->date)) {
                $result = $request->toDay($this->date, $this->dateType, true);
            } elseif (Numbers::isValid($request->get($this->name))) {
                $result = $request->getInt($this->name);
            } elseif ($request->has("{$this->name}Date")) {
                $result = $request->toDay("{$this->name}Date", $this->dateType, true);
            } else {
                $result = $request->toDay($this->name, $this->dateType, true);
            }
            break;
        case self::Hour:
            if (!empty($this->date) && !empty($this->hour)) {
                $result = $request->toTimeHour($this->date, $this->hour, true);
            } elseif ($request->has("{$this->name}Date") && $request->has("{$this->name}Hour")) {
                $result = $request->toTimeHour("{$this->name}Date", "{$this->name}Hour", true);
            }
            break;
        case self::JSON:
            $result = $request->toJSON($this->name);
            break;
        case self::CSV:
            $result = $request->toCSV($this->name);
            break;
        case self::Encrypt:
            if (!empty($masterKey)) {
                $value  = $request->get($this->name);
                $result = Query::encrypt($value, $masterKey);
            }
            break;
        default:
            $result = $request->get($this->name);
        }
        return $result;
    }

    /**
     * Returns the Field Values from the given Data
     * @param array{} $data
     * @return array{}
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
        case self::Binary:
        case self::Number:
            $result[$key]           = $number;
            break;
        case self::Float:
            $result[$key]           = Numbers::toFloat($number, $this->decimals);
            $result["{$key}Format"] = Numbers::formatInt($number, $this->decimals);
            $result["{$key}Int"]    = $number;
            break;
        case self::Price:
            $result[$key]           = Numbers::fromCents($number);
            $result["{$key}Format"] = Numbers::formatCents($number);
            $result["{$key}Cents"]  = $number;
            break;
        case self::Date:
        case self::Hour:
            $result[$key]           = $number;
            $result["{$key}Date"]   = !empty($number) ? date("d-m-Y",     $number) : "";
            $result["{$key}Full"]   = !empty($number) ? date("d-m-Y H:i", $number) : "";
            break;
        case self::JSON:
            $result[$key]           = JSON::decode($text, true);
            break;
        case self::CSV:
            $result[$key]           = $text;
            $result["{$key}Parts"]  = !empty($text) ? CSV::decode($text) : [];
            $result["{$key}Count"]  = Arrays::length($result["{$key}Parts"]);
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
                $result["{$key}Url"]   = !empty($text) ? FilePath::getUrl($this->path, $text) : "";
            } else {
                $result["{$key}Url"]   = !empty($text) ? FilePath::getUrl(FilePath::Source, "0", $text) : "";
                $result["{$key}Thumb"] = !empty($text) ? FilePath::getUrl(FilePath::Thumbs, "0", $text) : "";
            }
            break;
        default:
            $result[$key]           = $text;
        }
        return $result;
    }
}
