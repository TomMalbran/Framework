<?php
namespace Tests\Database\Fixture;

use Framework\Database\Type\SchemaRequest;
use Framework\File\File;
use Framework\Utils\Dictionary;

/**
 * A Schema Request with a property of every type it can be asked for
 */
class CrateRequest extends SchemaRequest {

    public string $name = "";

    public int $quantity = 0;

    public float $weight = 0;

    public bool $isOpen = false;

    public ?File $label = null;

    public ?Dictionary $extra = null;
}
