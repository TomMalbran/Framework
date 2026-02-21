<?php
namespace {{namespace}};

{{#imports}}use {{.}};
{{/imports}}{{#hasImports}}
{{/hasImports}}
use Framework\Database\Type\Entity;{{#hasDates}}
use Framework\Date\Date;{{/hasDates}}
use Framework\Utils\Dictionary;

/**
 * The {{name}} Entity
 */
class {{entityClass}} extends Entity {

{{#hasID}}
    {{#hasIntID}}
    public int $id = 0;
    {{/hasIntID}}
    {{#hasStringID}}
    public string $id = "";
    {{/hasStringID}}
    {{#hasEnumID}}
    public {{idEnumName}} $id = {{idEnumName}}::None;
    {{/hasEnumID}}


{{/hasID}}
{{#properties}}
    // {{name}} Fields
{{#list}}{{#subType}}
    /** @var {{{subType}}} */
{{/subType}}    public {{type}} ${{name}}{{#hasDefault}} = {{{default}}}{{/hasDefault}};
{{/list}}


{{/properties}}

    /**
     * Creates a new {{name}} Entity instance
     * @param Dictionary|null $data Optional.{{#attributes}}
     * @param {{{docType}}} ${{name}} Optional.{{/attributes}}{{#dates}}
     * @param Date|null ${{.}} Optional.{{/dates}}{{#hasStatus}}
     * @param {{statusClass}} $status Optional.{{/hasStatus}}
     */
    public function __construct(
        ?Dictionary $data = null,{{#attributes}}
        {{type}} ${{name}}{{#hasDefault}} = {{{default}}}{{/hasDefault}},{{/attributes}}{{#dates}}
        ?Date ${{.}} = null,{{/dates}}{{#hasStatus}}
        {{statusClass}} $status = {{statusClass}}::None,{{/hasStatus}}
    ) {
        // Set the Main Fields
        {{#attributes}}
        $this->{{name}} = ${{name}};
        {{/attributes}}
    {{#hasDates}}

        // Set the Dates
        {{#dates}}
        $this->{{.}} = ${{.}} ?? Date::empty();
        {{/dates}}
    {{/hasDates}}

        // Set all the Fields using the Dictionary
        if ($data !== null) {
            parent::__construct($data);
            {{#subTypes}}
            foreach ($data->getDict("{{name}}") as $index => $subData) {
                $this->{{name}}[$index] = new {{type}}Entity($subData);
            }
            {{/subTypes}}
        }
    {{#hasStatus}}

        // Set the Status
        if ($status !== {{statusClass}}::None) {
            $this->status = $status;
        }
        $this->statusName  = {{statusClass}}::getName($this->status);
        $this->statusColor = {{statusClass}}::getColor($this->status);
    {{/hasStatus}}
    {{#hasID}}

        // Set ID and isEmpty
        $this->id      = $this->{{idName}};
        {{#hasIntID}}
        $this->isEmpty = $this->{{idName}} === 0;
        {{/hasIntID}}
        {{#hasStringID}}
        $this->isEmpty = $this->{{idName}} === "";
        {{/hasStringID}}
        {{#hasEnumID}}
        $this->isEmpty = $this->{{idName}} === {{idEnumName}}::None;
        {{/hasEnumID}}
    {{/hasID}}
    }
}
