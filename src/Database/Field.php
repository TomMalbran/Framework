<?php
namespace Framework\Database;

use Framework\Request;
use Framework\File\FilePath;
use Framework\Database\Assign;
use Framework\System\Config;
use Framework\System\Path;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
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
    const Text     = "text";
    const LongText = "longtext";
    const JSON     = "json";
    const HTML     = "html";
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
    public string $default    = "";

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
     * @param string     $key
     * @param Dictionary $data
     * @param string     $prefix Optional.
     */
    public function __construct(string $key, Dictionary $data, string $prefix = "") {
        $this->key        = $key;

        $this->type       = $data->getString("type", self::String);
        $this->length     = $data->getInt("length", default: $this->length);
        $this->decimals   = $data->getInt("decimals", default: $this->decimals);
        $this->dateType   = $data->getString("dateType", $this->dateType);
        $this->date       = $data->getString("date", $this->date);
        $this->hour       = $data->getString("hour", $this->hour);
        $this->path       = $data->getString("path", $this->path);
        $this->default    = $data->getString("default", $this->default);

        $this->isID       = $this->type === self::ID;
        $this->isPrimary  = $this->isID || $data->hasValue("isPrimary");
        $this->isKey      = $data->hasValue("isKey");
        $this->isName     = $data->hasValue("isName");
        $this->isUnique   = $data->hasValue("isUnique");
        $this->isParent   = $data->hasValue("isParent");
        $this->noExists   = $data->hasValue("noExists");
        $this->noEmpty    = $data->hasValue("noEmpty");
        $this->isSigned   = $data->hasValue("isSigned");
        $this->noPrefix   = $data->hasValue("noPrefix");
        $this->canEdit    = !$data->hasValue("cantEdit");

        $this->mergeTo    = $data->getString("mergeTo");
        $this->defaultTo  = $data->getString("defaultTo");

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
        if ($this->prefix !== "" && !$this->noPrefix) {
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
            $length     = $this->length > 0 ? $this->length : 10;
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
            $length     = $this->length > 0 ? $this->length : 255;
            $attributes = "NOT NULL";
            $default    = "";
            break;
        case self::Text:
            $type       = "text";
            $attributes = "NULL";
            break;
        case self::LongText:
            $type       = "longtext";
            $attributes = "NULL";
            break;
        case self::JSON:
        case self::HTML:
            $type       = "mediumtext";
            $attributes = "NULL";
            break;
        case self::Encrypt:
            $type       = "varbinary";
            $length     = $this->length > 0 ? $this->length : 255;
            $attributes = "NOT NULL";
            break;
        }

        $result = $type;
        if ($withLength && $length > 0) {
            $result = "{$type}({$length}) $attributes";
        } elseif ($attributes !== "") {
            $result = "$type $attributes";
        }

        if ($result !== "unknown") {
            if ($this->default !== "") {
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
            if ($this->date !== "" && $this->hour !== "") {
                $result = $request->toTimeHour($this->date, $this->hour, true);
            } elseif ($this->date !== "") {
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
            $value  = $request->getString($this->name);
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
        $text   = isset($data[$key]) ? Strings::toString($data[$key]) : "";
        $number = isset($data[$key]) ? Numbers::toInt($data[$key])    : 0;
        $result = [];

        switch ($this->type) {
        case self::Boolean:
            $result[$key]           = !Arrays::isEmpty($data, $key);
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
        case self::Encrypt:
            $result[$key]           = isset($data["{$key}Decrypt"]) ? Strings::toString($data["{$key}Decrypt"]) : "";
            break;
        case self::File:
            $result[$key]           = $text;
            if ($this->path !== "") {
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
