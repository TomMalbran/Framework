<?php
namespace Framework\Database;

use Framework\Request;
use Framework\Database\Assign;
use Framework\Database\Model\FieldType;
use Framework\File\FilePath;
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

    // The Data
    public FieldType $type    = FieldType::None;

    public string $key        = "";
    public int    $decimals   = 2;
    public string $dateType   = "middle";
    public string $dateInput  = "";
    public string $hourInput  = "";
    public string $path       = "";
    public string $default    = "";

    public bool   $isID       = false;
    public bool   $isAutoInc  = false;
    public bool   $noExists   = false;
    public bool   $noEmpty    = false;
    public bool   $noPrefix   = false;
    public bool   $canEdit    = false;

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

        $this->type       = FieldType::from($data->getString("type"));
        $this->decimals   = $data->getInt("decimals", default: $this->decimals);
        $this->dateType   = $data->getString("dateType", $this->dateType);
        $this->dateInput  = $data->getString("dateInput", $this->dateInput);
        $this->hourInput  = $data->getString("hourInput", $this->hourInput);
        $this->path       = $data->getString("path", $this->path);
        $this->default    = $data->getString("default", $this->default);

        $this->isID       = $data->hasValue("isID");
        $this->isAutoInc  = $this->isID && $this->type === FieldType::Number;
        $this->noExists   = $data->hasValue("noExists");
        $this->noEmpty    = $data->hasValue("noEmpty");
        $this->noPrefix   = $data->hasValue("noPrefix");
        $this->canEdit    = !$data->hasValue("cantEdit");

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
     * Returns the Field Value from the given Request
     * @param Request $request
     * @return mixed
     */
    public function fromRequest(Request $request): mixed {
        $result = null;
        if ($this->isAutoInc) {
            return $result;
        }

        switch ($this->type) {
        case FieldType::Number:
            if ($this->dateInput !== "" && $this->hourInput !== "") {
                $result = $request->toTimeHour($this->dateInput, $this->hourInput, true);
            } elseif ($this->dateInput !== "") {
                $result = $request->toDay($this->dateInput, $this->dateType, true);
            } else {
                $result = $request->getInt($this->name);
            }
            break;
        case FieldType::Boolean:
            $result = $request->toBinary($this->name);
            break;
        case FieldType::String:
            $result = $request->getString($this->name);
            break;
        case FieldType::Float:
            $result = $request->toInt($this->name, $this->decimals);
            break;
        case FieldType::JSON:
            $result = $request->toJSON($this->name);
            break;
        case FieldType::Encrypt:
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
        case FieldType::Number:
            $result[$key]           = $number;
            break;
        case FieldType::Boolean:
            $result[$key]           = !Arrays::isEmpty($data, $key);
            break;
        case FieldType::Float:
            $result[$key]           = Numbers::toFloat($number, $this->decimals);
            break;
        case FieldType::JSON:
            $result[$key]           = JSON::decodeAsArray($text);
            break;
        case FieldType::Encrypt:
            $result[$key]           = isset($data["{$key}Decrypt"]) ? Strings::toString($data["{$key}Decrypt"]) : "";
            break;
        case FieldType::File:
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
