<?php
namespace Framework\Auth;

use Framework\Schema\Model;
use Framework\Utils\Enum;

/**
 * The Documents used by the System
 */
class Document extends Enum {

    const DNI   = 1;
    const CUIT  = 2;
    const Other = 3;


    /**
     * Returns all the Document Types for the given Credential
     * @param Model|array{} $credential
     * @return integer
     */
    public static function forCredential(Model|array $credential): int {
        if (!empty($credential["dni"])) {
            return self::DNI;
        }
        if (!empty($credential["cuit"])) {
            return self::CUIT;
        }
        if (!empty($credential["taxID"])) {
            return self::Other;
        }
        return 0;
    }
}
