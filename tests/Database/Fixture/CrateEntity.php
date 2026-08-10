<?php
namespace Tests\Database\Fixture;

use Framework\Core\VariableType;
use Framework\Database\Type\Entity;
use Framework\Date\Date;
use Framework\File\File;
use Framework\Utils\Dictionary;

/**
 * An Entity holding a property of every type the Entity knows how to read
 */
class CrateEntity extends Entity {

    public string $name = "";

    public int $amount = 0;

    public float $weight = 0;

    public bool $isOpen = false;

    public VariableType $kind = VariableType::None;

    // The three objects are nullable so that an Entity built from part of a
    // row can still be walked, which is what toArray() does to every property
    public ?Date $sentTime = null;

    public ?Dictionary $extra = null;

    public ?File $label = null;

    /** @var array<string,mixed> */
    public array $tags = [];
}
