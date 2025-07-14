<?php
namespace Framework\Database\Model;

use Framework\Discovery\Discovery;
use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;
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


    // Used to indicate that the field should not change when using the edit functions with a Request
    public bool   $canEdit    = true;

    // FIXME: Remove this temporary properties
    public bool   $noEmpty    = false;
    public bool   $noExists   = false;
    public bool   $notPrimary = false;


    // Used internally when parsing the Model
    public FieldType $type       = FieldType::String;
    public string    $key        = "";
    public string    $name       = "";
    public string    $prefixName = "";
    public bool      $noPrefix   = false;



    /**
     * The Field Attribute
     * @param boolean   $isParent   Optional.
     * @param boolean   $isUnique   Optional.
     * @param string    $belongsTo  Optional.
     * @param string    $otherKey   Optional.
     * @param boolean   $isID       Optional.
     * @param boolean   $isPrimary  Optional.
     * @param boolean   $isKey      Optional.
     * @param int       $length     Optional.
     * @param boolean   $isSigned   Optional.
     * @param int       $decimals   Optional.
     * @param boolean   $isText     Optional.
     * @param boolean   $isLongText Optional.
     * @param boolean   $isEncrypt  Optional.
     * @param boolean   $isJSON     Optional.
     * @param boolean   $isFile     Optional.
     * @param string    $dateType   Optional.
     * @param string    $dateInput  Optional.
     * @param string    $hourInput  Optional.
     * @param boolean   $canEdit    Optional.
     * @param boolean   $noEmpty    Optional.
     * @param boolean   $noExists   Optional.
     * @param boolean   $notPrimary Optional.
     * @param string    $name       Optional.
     * @param FieldType $type       Optional.
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

        bool   $canEdit    = true,
        bool   $noEmpty    = false,
        bool   $noExists   = false,
        bool   $notPrimary = false,

        string    $name    = "",
        FieldType $type    = FieldType::String,
    ) {
        $this->isParent   = $isParent;
        $this->isUnique   = $isUnique;
        $this->belongsTo  = $belongsTo;
        $this->otherKey   = $otherKey;

        $this->isID       = $isID;
        $this->isPrimary  = $isPrimary;
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

        $this->canEdit    = $canEdit;
        $this->noEmpty    = $noEmpty;
        $this->noExists   = $noExists;
        $this->notPrimary = $notPrimary;

        $this->key        = self::generateKey($name);
        $this->name       = $name;
        $this->type       = $type;
    }



    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Field
     */
    public function setData(string $name, string $typeName): Field {
        $this->key  = self::generateKey($name);
        $this->name = $name;

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
     * Returns the name of the field for the Schema
     * @return string
     */
    public function getName(): string {
        $name = $this->name;

        // If the field is not a primary key, we don't convert the name
        if ($this->notPrimary) {
            return $name;
        }

        // Assume is a primary field if is an ID or Number and ends with "ID"
        if ($this->isSchemaID()) {
            $name = self::generateKey($name);
        }
        return $name;
    }

    /**
     * Returns true if the field is a Schema ID
     * @return bool
     */
    public function isSchemaID(): bool {
        return $this->isID || (
            $this->type === FieldType::Number && Strings::endsWith($this->name, "ID")
        );
    }

    /**
     * Returns true if the field is an Auto Increment
     * @return bool
     */
    public function isAutoInc(): bool {
        return $this->isID && $this->type === FieldType::Number;
    }

    /**
     * Generates the key of the field for the Schema
     * @param string $name
     * @return string
     */
    public static function generateKey(string $name): string {
        if (Strings::endsWith($name, "ID")) {
            $name = Strings::replace($name, "ID", "Id");
            $name = Strings::camelCaseToUpperCase($name);
        }
        return $name;
    }



    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $skips      = [ "key", "name", "prefixName", "type", "belongsTo", "otherKey", "isText", "isLongText", "isEncrypt", "isJSON", "isFile", "notPrimary" ];
        $properties = Discovery::getProperties($this);
        $defaults   = new Field();
        $result     = [
            "type" => $this->type->getName(),
        ];

        foreach ($properties as $name => $type) {
            if (Arrays::contains($skips, $name)) {
                continue;
            }

            if ($name === "canEdit" && !$this->canEdit) {
                $result["cantEdit"] = true;
            } elseif ($defaults->$name !== $this->$name) {
                $result[$name] = $this->$name;
            }
        }
        return $result;
    }
}
