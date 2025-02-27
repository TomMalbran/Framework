<?php
namespace {{namespace}}Schema;

{{#subTypes}}use {{appNamespace}}Schema\{{type}}Entity;
{{/subTypes}}
use Framework\Database\Entity;

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
