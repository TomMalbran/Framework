<?php
namespace {{namespace}};

{{#subTypes}}use {{namespace}}\{{type}}Entity;
{{/subTypes}}
use Framework\Database\Type\Entity;

/**
 * The {{name}} Entity
 */
class {{name}}Entity extends Entity {

    protected const ID = "{{id}}";

{{#attributes}}{{#subType}}

    /** @var {{{subType}}} */
{{/subType}}    public {{type}} ${{name}} = {{{default}}};
{{/attributes}}

}
