<?php
namespace {{namespace}};

use Framework\Provider\Mustache;
use Framework\File\File;

/**
 * The Template
 */
enum Template {

    case None;

{{#templates}}
    case {{name}};
{{/templates}}



    /**
     * Creates a File rendering the Template
     * @param string $path
     * @param array<string,mixed> $data
     * @return string
     */
    public function create(string $path, array $data): string {
        return File::create($path, "{$this->name}.php", $this->render($data));
    }

    /**
     * Renders the Template with the given Data
     * @param array<string,mixed> $data
     * @return string
     */
    public function render(array $data): string {
        $path = match ($this) {
        {{#templates}}
            self::{{constant}} => "{{path}}",
        {{/templates}}
            default => "",
        };
        if ($path === "") {
            return "";
        }

        $code = File::read($path);
        return Mustache::render($code, $data);
    }
}
