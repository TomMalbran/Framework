<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\File\FilePath;
use Framework\System\Path;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Field Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field {

    // The field is the key of a parent Schema and is used in several Schema functions
    public bool   $isParent   = false;

    // The field values are unique which adds special functions in the Schema
    public bool   $isUnique   = false;

    // Marks the field as being the ID of a different Schema
    // Is not used in the code, but it can be used to create a DER
    public string $belongsTo  = "";

    // Name of the column in the Model it belongs to
    // If not set, it will be the same as the name of the field
    // Is not used in the code, but it can be used to create a DER
    public string $otherKey   = "";


    // Makes the field a primary key and auto-increment
    public bool   $isID       = false;

    // Makes the field a primary key in SQL
    public bool   $isPrimary  = false;

    // Makes the field an index key in SQL
    public bool   $isKey      = false;


    // Indicates the length of the number or string which determines the SQL type
    // For numbers the length is 10 making the type as int(10)
    // For strings the length is 255 making the type as varchar(255)
    public int    $length     = 0;

    // Indicates that the integer can be negative
    public bool   $isSigned   = false;

    // When the type is float, this indicates the number of decimals
    public int    $decimals   = 2;


    // Uses the type text in MySQL instead of varchar for strings
    public bool   $isText     = false;

    // Uses the type longtext in MySQL instead of varchar for strings
    public bool   $isLongText = false;

    // Indicates that the string is encrypted in the database
    public bool   $isEncrypt  = false;

    // Indicates that the string is an encoded JSON
    public bool   $isJSON     = false;

    // Indicates that the string is a relative path to a file
    public bool   $isFile     = false;


    // Used to convert a date to a timeStamp that comes from an input
    public string $dateInput  = "";

    // Used with the 'dateInput' to indicate if the hour of the date is at:
    // 'start', 'middle' or 'end'
    public string $dateType   = "";

    // Used with the 'dateInput' to indicate that the hour comes from an input
    public string $hourInput  = "";

    // Used to generate the path and url of the file
    public string $filePath   = "";


    // Used to indicate that the field should not change when using the edit functions with a Request
    public bool   $canEdit    = true;

    // FIXME: Remove this temporary properties
    public bool   $noEmpty    = false;
    public bool   $noExists   = false;


    /**
     * The Field Attribute
     * @param boolean $isParent   Optional.
     * @param boolean $isUnique   Optional.
     * @param string  $belongsTo  Optional.
     * @param string  $otherKey   Optional.
     * @param boolean $isID       Optional.
     * @param boolean $isPrimary  Optional.
     * @param boolean $isKey      Optional.
     * @param int     $length     Optional.
     * @param boolean $isSigned   Optional.
     * @param int     $decimals   Optional.
     * @param boolean $isText     Optional.
     * @param boolean $isLongText Optional.
     * @param boolean $isEncrypt  Optional.
     * @param boolean $isJSON     Optional.
     * @param boolean $isFile     Optional.
     * @param string  $dateType   Optional.
     * @param string  $dateInput  Optional.
     * @param string  $hourInput  Optional.
     * @param string  $filePath   Optional.
     * @param boolean $canEdit    Optional.
     * @param boolean $noEmpty    Optional.
     * @param boolean $noExists   Optional.
     */
    public function __construct(
        bool   $isParent   = false,
        bool   $isUnique   = false,
        string $belongsTo  = "",
        string $otherKey   = "",

        bool   $isID       = false,
        bool   $isPrimary  = false,
        bool   $isKey      = false,

        int    $length     = 0,
        bool   $isSigned   = false,
        int    $decimals   = 2,

        bool   $isText     = false,
        bool   $isLongText = false,
        bool   $isEncrypt  = false,
        bool   $isJSON     = false,
        bool   $isFile     = false,

        string $dateType   = "",
        string $dateInput  = "",
        string $hourInput  = "",
        string $filePath   = "",

        bool   $canEdit    = true,
        bool   $noEmpty    = false,
        bool   $noExists   = false,
    ) {
        $this->isParent   = $isParent;
        $this->isUnique   = $isUnique;
        $this->belongsTo  = $belongsTo;
        $this->otherKey   = $otherKey;

        $this->isID       = $isID;
        $this->isPrimary  = $isID || $isPrimary;
        $this->isKey      = $isKey;

        $this->length     = $length;
        $this->isSigned   = $isSigned;
        $this->decimals   = $decimals;

        $this->isText     = $isText;
        $this->isLongText = $isLongText;
        $this->isEncrypt  = $isEncrypt;
        $this->isJSON     = $isJSON;
        $this->isFile     = $isFile;

        $this->dateType   = $dateType;
        $this->dateInput  = $dateInput;
        $this->hourInput  = $hourInput;
        $this->filePath   = $filePath;

        $this->canEdit    = $canEdit;
        $this->noEmpty    = $noEmpty;
        $this->noExists   = $noExists;
    }



    // Used internally when parsing the Model
    public FieldType $type       = FieldType::String;
    public string    $name       = "";
    public string    $dbName     = "";
    public string    $prefixName = "";


    /**
     * Creates a Field
     * @param string    $name      Optional.
     * @param string    $dbName    Optional.
     * @param FieldType $type      Optional.
     * @param boolean   $isID      Optional.
     * @param boolean   $isPrimary Optional.
     * @param boolean   $isKey     Optional.
     * @param int       $length    Optional.
     * @param boolean   $isSigned  Optional.
     * @param int       $decimals  Optional.
     * @param string    $dateType  Optional.
     * @param string    $dateInput Optional.
     * @param string    $hourInput Optional.
     * @param string    $filePath  Optional.
     * @param boolean   $canEdit   Optional.
     * @param boolean   $noEmpty   Optional.
     * @param boolean   $noExists  Optional.
     * @return Field
     */
    public static function create(
        string    $name   = "",
        string    $dbName = "",
        FieldType $type   = FieldType::String,

        bool   $isID      = false,
        bool   $isPrimary = false,
        bool   $isKey     = false,

        int    $length    = 0,
        bool   $isSigned  = false,
        int    $decimals  = 2,

        string $dateType  = "",
        string $dateInput = "",
        string $hourInput = "",
        string $filePath  = "",

        bool   $canEdit   = true,
        bool   $noEmpty   = false,
        bool   $noExists  = false,
    ): Field {
        $result = new self(
            isID:      $isID,
            isPrimary: $isPrimary,
            isKey:     $isKey,
            length:    $length,
            isSigned:  $isSigned,
            decimals:  $decimals,
            dateType:  $dateType,
            dateInput: $dateInput,
            hourInput: $hourInput,
            filePath:  $filePath,
            canEdit:   $canEdit,
            noEmpty:   $noEmpty,
            noExists:  $noExists,
        );
        $result->type   = $type;
        $result->name   = $name;
        $result->dbName = $dbName !== "" ? $dbName : $name;
        return $result;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Field
     */
    public function setData(string $name, string $typeName): Field {
        $this->name   = $name;
        $this->dbName = $name;

        switch ($typeName) {
        case "bool":
            $this->type = FieldType::Boolean;
            break;

        case "float":
            $this->type = FieldType::Float;
            break;

        case "int":
            $this->type = FieldType::Number;
            break;

        case "string":
            if ($this->isText) {
                $this->type = FieldType::Text;
            } elseif ($this->isLongText) {
                $this->type = FieldType::LongText;
            } elseif ($this->isEncrypt) {
                $this->type = FieldType::Encrypt;
            } elseif ($this->isJSON) {
                $this->type = FieldType::JSON;
            } elseif ($this->isFile) {
                $this->type = FieldType::File;
            } else {
                $this->type = FieldType::String;
            }
            break;

        default:
            $this->type = FieldType::String;
            break;
        }

        return $this;
    }

    /**
     * Sets the Database Name of the Field
     * @return Field
     */
    public function setDbName(): Field {
        $this->dbName = SchemaModel::getDbFieldName($this->name);
        return $this;
    }



    /**
     * Returns true if the field is a Schema ID
     * @return bool
     */
    public function isSchemaID(): bool {
        return $this->name !== $this->dbName;
    }

    /**
     * Returns true if the field is an Auto Increment
     * @return bool
     */
    public function isAutoInc(): bool {
        return $this->isID && $this->type === FieldType::Number;
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
        case FieldType::Number:
            $type    = "int";
            $length  = $this->length > 0 ? $this->length : 10;
            $default = 0;

            if ($length < 3) {
                $type = "tinyint";
            } elseif ($length < 5) {
                $type = "smallint";
            } elseif ($length < 8) {
                $type = "mediumint";
            } elseif ($length > 10) {
                $type = "bigint";
            }

            if ($this->isAutoInc()) {
                $attributes = "unsigned NOT NULL AUTO_INCREMENT";
                $default    = null;
            } else {
                $attributes = $this->isSigned ? "NOT NULL" : "unsigned NOT NULL";
            }
            break;
        case FieldType::Boolean:
            $type       = "tinyint";
            $length     = 1;
            $attributes = "unsigned NOT NULL";
            $default    = 0;
            break;
        case FieldType::Float:
            $type       = "bigint";
            $length     = 20;
            $attributes = $this->isSigned ? "NOT NULL" : "unsigned NOT NULL";
            $default    = 0;
            break;
        case FieldType::String:
        case FieldType::File:
            $type       = "varchar";
            $length     = $this->length > 0 ? $this->length : 255;
            $attributes = "NOT NULL";
            $default    = "";
            break;
        case FieldType::Text:
            $type       = "text";
            $attributes = "NULL";
            break;
        case FieldType::LongText:
            $type       = "longtext";
            $attributes = "NULL";
            break;
        case FieldType::JSON:
            $type       = "mediumtext";
            $attributes = "NULL";
            break;
        case FieldType::Encrypt:
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

        if ($result !== "unknown" && $default !== null) {
            if (is_numeric($default)) {
                $result .= " DEFAULT {$default}";
            } else {
                $result .= " DEFAULT '{$default}'";
            }
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
            $result[$key] = $number;
            break;
        case FieldType::Boolean:
            $result[$key] = !Arrays::isEmpty($data, $key);
            break;
        case FieldType::Float:
            $result[$key] = Numbers::toFloat($number, $this->decimals);
            break;
        case FieldType::JSON:
            $result[$key] = JSON::decodeAsArray($text);
            break;
        case FieldType::Encrypt:
            $result[$key] = isset($data["{$key}Decrypt"]) ? Strings::toString($data["{$key}Decrypt"]) : "";
            break;
        case FieldType::File:
            $result[$key] = $text;
            if ($this->filePath !== "") {
                $result["{$key}Url"]   = $text !== "" ? FilePath::getUrl($this->filePath, $text) : "";
            } else {
                $result["{$key}Url"]   = $text !== "" ? Path::getSourceUrl("0", $text) : "";
                $result["{$key}Thumb"] = $text !== "" ? Path::getThumbsUrl("0", $text) : "";
            }
            break;
        default:
            $result[$key] = $text;
        }

        return $result;
    }



    /**
     * Returns the Data to build a Field
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        $result = [
            "name" => $this->name,
            "type" => $this->type,
        ];
        if ($this->name !== $this->dbName) {
            $result["dbName"] = $this->dbName;
        }
        if ($this->isID) {
            $result["isID"] = $this->isID;
        }
        if ($this->decimals !== 2) {
            $result["decimals"] = $this->decimals;
        }
        if ($this->dateType !== "") {
            $result["dateType"] = $this->dateType;
        }
        if ($this->dateInput !== "") {
            $result["dateInput"] = $this->dateInput;
        }
        if ($this->hourInput !== "") {
            $result["hourInput"] = $this->hourInput;
        }
        if ($this->filePath !== "") {
            $result["filePath"] = $this->filePath;
        }
        if (!$this->canEdit) {
            $result["canEdit"] = $this->canEdit;
        }
        if ($this->noEmpty) {
            $result["noEmpty"] = $this->noEmpty;
        }
        if ($this->noExists) {
            $result["noExists"] = $this->noExists;
        }
        return $result;
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $result = [
            "type" => $this->type->getName(),
        ];
        if ($this->isID) {
            $result["isID"] = true;
        }
        if ($this->isPrimary && !$this->isID) {
            $result["isPrimary"] = true;
        }
        if ($this->isKey) {
            $result["isKey"] = true;
        }

        if ($this->length > 0) {
            $result["length"] = $this->length;
        }
        if ($this->isSigned) {
            $result["isSigned"] = true;
        }
        if ($this->decimals !== 2) {
            $result["decimals"] = $this->decimals;
        }

        if ($this->dateType !== "") {
            $result["dateType"] = $this->dateType;
        }
        if ($this->dateInput !== "") {
            $result["dateInput"] = $this->dateInput;
        }
        if ($this->hourInput !== "") {
            $result["hourInput"] = $this->hourInput;
        }
        if ($this->filePath !== "") {
            $result["path"] = $this->filePath;
        }

        if ($this->isUnique) {
            $result["isUnique"] = true;
        }
        if ($this->isParent) {
            $result["isParent"] = true;
        }
        if ($this->noExists) {
            $result["noExists"] = true;
        }
        if ($this->noEmpty) {
            $result["noEmpty"] = true;
        }
        if (!$this->canEdit) {
            $result["cantEdit"] = true;
        }
        return $result;
    }
}
