<?php
namespace Framework\Log\Attr;

use Framework\Utils\Arrays;

use Attribute;

/**
 * The Action Attribute
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Action {

    public string $name = "";

    /** @var array<string,string> */
    public array $translations = [];



    /**
     * The Action Attribute
     * @param string $name
     * @param string ...$translations
     */
    public function __construct(string $name, string ...$translations) {
        $this->name         = $name;
        $this->translations = Arrays::toStringsMap($translations);
    }
}
