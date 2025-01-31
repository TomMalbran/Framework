<?php
namespace {{namespace}}Schema;

{{#subTypes}}use {{appNamespace}}Schema\{{type}}Entity;
{{/subTypes}}
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
