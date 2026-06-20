<?php
namespace Framework\Log\Attr;

use Framework\Utils\Arrays;

use Attribute;

/**
 * The Section Attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Section {

    public string $name = "";

    /** @var array<string,string> */
    public array $translations = [];



    /**
     * The Section Attribute
     * @param string $name
     * @param string ...$translations
     */
    public function __construct(string $name, string ...$translations) {
        $this->name         = $name;
        $this->translations = Arrays::toStringsMap($translations);
    }
}
