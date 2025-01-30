<?php
namespace {{namespace}}Schema;

use Framework\Database\Entity;

/**
 * The {{name}} Entity
 */
class {{name}}Entity extends Entity {

{{#attributes}}{{#subType}}

    /** @var {{{subType}}} */
{{/subType}}    public {{type}} ${{name}} = {{{default}}};
{{/attributes}}

}
