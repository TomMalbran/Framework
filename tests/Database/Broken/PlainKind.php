<?php
namespace Tests\Database\Broken;

/**
 * An Enum of PHP and nothing more, which a Field cannot be typed as
 */
enum PlainKind {

    case None;

    case First;
}
