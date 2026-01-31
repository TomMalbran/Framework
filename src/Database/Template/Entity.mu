<?php
namespace {{namespace}};

{{#subTypes}}use {{namespace}}\{{type}}Entity;
{{/subTypes}}
use Framework\Database\Type\Entity;{{#hasDates}}
use Framework\Date\Date;{{/hasDates}}

/**
 * The {{name}} Entity
 */
class {{name}}Entity extends Entity {

    protected const ID = "{{id}}";
{{#categories}}


    // {{name}} Fields
{{#attributes}}{{#subType}}
    /** @var {{{subType}}} */
{{/subType}}    public {{type}} ${{name}}{{#hasDefault}} = {{{default}}}{{/hasDefault}};
{{/attributes}}
{{/categories}}
{{#hasDates}}



    /**
     * Creates a new {{name}} Entity instance
     * @param mixed $data Optional.
     */
    public function __construct(mixed $data = null) {
        {{#dates}}
        $this->{{.}} = Date::empty();
        {{/dates}}
        parent::__construct($data);
    }
{{/hasDates}}
}
