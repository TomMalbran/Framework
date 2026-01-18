<?php
namespace {{namespace}};

use Framework\Application;
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
     * Returns the File Name of the Template
     * @return string
     */
    public function getFileName(): string {
        return "{$this->name}.php";
    }

    /**
     * Creates a File rendering the Template
     * @param string $path
     * @param array<string,mixed> $data
     * @return bool
     */
    public function create(string $path, array $data): bool {
        return File::create($path, $this->getFileName(), $this->render($data));
    }

    /**
     * Renders the Template with the given Data
     * @param array<string,mixed> $data
     * @return string
     */
    public function render(array $data): string {
        $relPath = match ($this) {
        {{#templates}}
            self::{{constant}} => "{{relPath}}",
        {{/templates}}
            default => "",
        };
        if ($relPath === "") {
            return "";
        }

        $path = Application::getBasePath($relPath);
        $code = File::read($path);
        return Mustache::render($code, $data);
    }
}
