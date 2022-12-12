<?php
namespace Framework\Utils;

use Framework\Schema\Model;
use Framework\Utils\Enum;

/**
 * The IVA Categories used by the System
 */
class IVACategory extends Enum {

    const Mon = 1;
    const RI  = 2;
    const CF  = 3;
    const Ex  = 4;


    /** All the posible Values */
    public static $data = [
        self::Mon => [
            "key"          => self::Mon,
            "name"         => "Responsable Monotributo",
            "abbreviation" => "Mon.",
            "type"         => "B",
            "requiresCUIT" => true,
            "separateIVa"  => true,
        ],
        self::RI  => [
            "key"          => self::RI,
            "name"         => "Responsable Inscripto",
            "abbreviation" => "R.I.",
            "type"         => "A",
            "requiresCUIT" => true,
            "separateIVa"  => false,
        ],
        self::CF  => [
            "key"          => self::CF,
            "name"         => "Consumidor Final",
            "abbreviation" => "C.F.",
            "type"         => "B",
            "requiresCUIT" => false,
            "separateIVa"  => false,
        ],
        self::Ex  => [
            "key"          => self::Ex,
            "name"         => "Exento",
            "abbreviation" => "Ex.",
            "type"         => "B",
            "requiresCUIT" => true,
            "separateIVa"  => false,
        ],
    ];



    /**
     * Returns the IVA Categories that requires a CUIT
     * @return array
     */
    public static function withCUIT() {
        $result = [];
        foreach (self::$data as $key => $data) {
            if ($data["requiresCUIT"]) {
                $result[] = $key;
            }
        }
        return $result;
    }



    /**
     * Returns the Type and Document for the given Model
     * @param Model $model
     * @return array
     */
    public static function getTypeAndDocument(Model $model): array {
        $cuit   = $model->getInt("cuit");
        $dni    = $model->getInt("dni");
        $result = [
            "type"     => "",
            "document" => "",
            "combined" => "",
        ];


        if ($model->has("ivaCategory") && self::requiresCUIT($model->ivaCategory)) {
            $result = [
                "type"     => "CUIT",
                "number"   => $cuit,
                "combined" => "CUIT {$cuit}",
            ];
        } else {
            $result = [
                "type"     => "DNI",
                "number"   => $dni,
                "combined" => "DNI {$dni}",
            ];
            $result = "DNI {$dni}";
        }
        return $result;
    }
}
