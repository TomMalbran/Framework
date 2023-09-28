<?php
namespace App\Schema;

use Framework\Schema\Entity;

/**
 * The {{name}} Entity
 */
class {{name}}Entity extends Entity {

{{#attributes}}
    public {{type}} ${{name}} = {{{default}}};
{{/attributes}}

}
