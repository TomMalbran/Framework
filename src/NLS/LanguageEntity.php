<?php
namespace Framework\NLS;

use Framework\Database\Entity;

/**
 * The Language Entity
 */
class LanguageEntity extends Entity {

    public string $key    = "";
    public string $name   = "";
    public bool   $isRoot = false;

}
