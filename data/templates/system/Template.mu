<?php
namespace {{namespace}};

use Framework\Discovery\Discovery;
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
        $relPath = match ($this) {
        {{#templates}}
            self::{{constant}} => "{{relPath}}",
        {{/templates}}
            default => "",
        };
        if ($relPath === "") {
            return "";
        }

        $path = Discovery::getAppPath($relPath);
        $code = File::read($path);
        return Mustache::render($code, $data);
    }
}
