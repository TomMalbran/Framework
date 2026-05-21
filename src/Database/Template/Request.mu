<?php
namespace {{namespace}};

{{#hasStatus}}
use {{namespace}}\{{statusClass}};

{{/hasStatus}}
{{#hasImports}}
{{#imports}}use {{.}};
{{/imports}}

{{/hasImports}}
use Framework\IO\Request;
use Framework\Database\Type\SchemaRequest;{{#hasDictionaries}}
use Framework\Utils\Dictionary;{{/hasDictionaries}}

/**
 * The {{name}} Request
 */
class {{requestClass}} extends SchemaRequest {
{{#hasID}}

    public bool $isCreate;
    public bool $isEdit;

    {{#hasIntID}}
    public int $id;
    {{/hasIntID}}
    {{#hasStringID}}
    public string $code;
    {{/hasStringID}}
    {{#hasEnumID}}
    public {{idEnumName}} $code = {{idEnumName}}::None;
    {{/hasEnumID}}
{{/hasID}}
{{#hasMultiID}}

    {{#hasIntID}}
    /** @var list<int> */
    public array $ids;
    {{/hasIntID}}
    {{#hasStringID}}
    /** @var list<string> */
    public array $codes;
    {{/hasStringID}}
{{/hasMultiID}}
{{#hasFields}}

    // Fields
    {{#fields}}
    {{#subType}}

    /** @var {{{subType}}} */
{{/subType}}    public {{type}} ${{name}};
    {{/fields}}
{{/hasFields}}
{{#hasValues}}

    // Values
    {{#values}}
    public {{type}} ${{name}};
    {{/values}}
{{/hasValues}}
{{#hasDictionaries}}

    // Dictionaries
    {{#dictionaries}}
    public Dictionary ${{.}};
    {{/dictionaries}}
{{/hasDictionaries}}


    /**
     * Creates a new {{requestClass}} instance
     * @param SchemaRequest|Request|null $request Optional.
     */
    public function __construct(SchemaRequest|Request|null $request = null) {
        parent::__construct($request);
    {{#hasID}}

        $this->isCreate = !$this->request->has("{{idName}}");
        $this->isEdit = $this->request->has("{{idName}}");

        {{#hasIntID}}
        $this->id = $this->request->getInt("{{idName}}");
        {{/hasIntID}}
        {{#hasStringID}}
        $this->code = $this->request->getString("{{idName}}");
        {{/hasStringID}}
        {{#hasEnumID}}
        $this->code = {{idEnumName}}::fromRequest($this->request, "{{idName}}");
        {{/hasEnumID}}
    {{/hasID}}
    {{#hasMultiID}}
        {{#hasIntID}}
        $this->ids = $this->request->getInts("{{idName}}s");
        {{/hasIntID}}
        {{#hasStringID}}
        $this->codes = $this->request->getStrings("{{idName}}s");
        {{/hasStringID}}
    {{/hasMultiID}}
    {{#hasFields}}

        // Set the Fields
        {{#fields}}
        $this->{{name}} = {{{getter}}};
        {{/fields}}
    {{/hasFields}}
    {{#hasValues}}

        // Set the Values
        {{#values}}
        $this->{{name}} = new {{type}}($this->request, "{{value}}"{{{extras}}});
        {{/values}}
    {{/hasValues}}
    {{#hasDictionaries}}

        // Set the Dictionaries
        {{#dictionaries}}
        $this->{{.}} = $this->request->getDict("{{.}}");
        {{/dictionaries}}
    {{/hasDictionaries}}
    }
{{#hasStatus}}

    /**
     * Returns the Status from the Request
     * @return {{statusClass}}
     */
    public function getStatus(): {{statusClass}} {
        return {{statusClass}}::fromRequest($this->request, "status");
    }
{{/hasStatus}}
}
