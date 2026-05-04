<?php
namespace {{namespace}};

{{#hasStatus}}
use {{namespace}}\{{statusClass}};

{{/hasStatus}}
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
    public string $code;
    {{/hasEnumID}}
    {{#parents}}
    public {{type}} ${{name}};
    {{/parents}}

    {{#properties}}
    public {{type}} ${{name}};
    {{/properties}}

    {{#dictionaries}}
    public Dictionary ${{.}};
    {{/dictionaries}}


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
        $this->code = $request->getString("{{idName}}");
        {{/hasEnumID}}
        {{#parents}}
        $this->{{name}} = $request->{{getter}}("{{name}}");
        {{/parents}}

        {{#properties}}
        $this->{{name}} = new {{type}}($request, "{{value}}"{{{extras}}});
        {{/properties}}

        {{#dictionaries}}
        $this->{{.}} = $request->getDictionary("{{.}}");
        {{/dictionaries}}
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
