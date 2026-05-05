<?php
namespace {{namespace}};

{{#hasStatus}}
use {{namespace}}\{{statusClass}};

{{/hasStatus}}
{{#imports}}use {{.}};
{{/imports}}{{#hasImports}}
{{/hasImports}}
use Framework\IO\Request;{{#values}}
use Framework\IO\Value\{{.}};{{/values}}
use Framework\Utils\Dictionary;

/**
 * The {{name}} Request
 */
class {{requestClass}} {

    private Request $request;

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
{{#hasNatives}}

    // Native Fields
    {{#natives}}
    public {{type}} ${{name}};
    {{/natives}}
{{/hasNatives}}
{{#hasProperties}}

    // Properties
    {{#properties}}
    public {{type}} ${{name}};
    {{/properties}}
{{/hasProperties}}
{{#hasDictionaries}}

    // Dictionaries
    {{#dictionaries}}
    public Dictionary ${{.}};
    {{/dictionaries}}
{{/hasDictionaries}}


    /**
     * Creates a new {{requestClass}} instance
     * @param Request|null $request Optional.
     */
    public function __construct(?Request $request = null) {
        if ($request === null) {
            $request = new Request();
        }

        $this->request = $request;
        $this->isCreate = !$request->has("{{idName}}");
        $this->isEdit = $request->has("{{idName}}");

        {{#hasIntID}}
        $this->id = $request->getInt("{{idName}}");
        {{/hasIntID}}
        {{#hasStringID}}
        $this->code = $request->getString("{{idName}}");
        {{/hasStringID}}
        {{#hasEnumID}}
        $this->code = {{idEnumName}}::fromRequest($request, "{{idName}}");
        {{/hasEnumID}}
    {{#hasMultiID}}
        {{#hasIntID}}
        $this->ids = $request->getInts("{{idName}}s");
        {{/hasIntID}}
        {{#hasStringID}}
        $this->codes = $request->getStrings("{{idName}}s");
        {{/hasStringID}}
    {{/hasMultiID}}
    {{#hasNatives}}

        // Set the Native Fields
        {{#natives}}
        $this->{{name}} = {{{getter}}};
        {{/natives}}
    {{/hasNatives}}
    {{#hasProperties}}

        // Set the Properties
        {{#properties}}
        $this->{{name}} = new {{type}}($request, "{{value}}"{{{extras}}});
        {{/properties}}
    {{/hasProperties}}
    {{#hasDictionaries}}

        // Set the Dictionaries
        {{#dictionaries}}
        $this->{{.}} = $request->getDict("{{.}}");
        {{/dictionaries}}
    {{/hasDictionaries}}
    }

    /**
     * Returns true if the Request is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return !$this->request->has();
    }

    /**
     * Returns true if the Request is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return $this->request->has();
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

    /**
     * Returns the original Request
     * @return Request
     */
    public function getRequest(): Request {
        return $this->request;
    }

    /**
     * Converts the Request to a Dictionary
     * @return Dictionary
     */
    public function toDictionary(): Dictionary {
        return $this->request->toDictionary();
    }
}
