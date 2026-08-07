# Framework

My personal PHP Framework: routes, models, listeners and settings are found by their
attributes, turned into typed classes by a build step, and driven from a single
`./framework` CLI.

**[Read the documentation →](https://tommalbran.github.io/Framework/)**


## Requirements

PHP 8.1 or newer with the `mysqli`, `curl` and `zip` extensions, and a MySQL database.


## Installation

Add the repository to the repositories:
```json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/TomMalbran/Framework.git"
    }
]
```

Add the require using the corresponding version:
```json
"require": {
    "tommalbran/framework": "dev-main#v0.17.0"
}
```

Execute the installer:
```
./vendor/bin/framework install
```

See [Getting Started](https://tommalbran.github.io/Framework/introduction/getting-started.html)
for the entry point, the `.env` files and the first build.


## The CLI

| Command | Does |
| --- | --- |
| `install` | Installs the console command and the phpcs config into the app. |
| `build` | Generates the typed code into `src/System` from the discovered attributes. |
| `destroy` | Removes the generated code. |
| `watch` | Rebuilds whenever a source file changes. |
| `migrate` | Applies the schema and data migrations. |
| `migration` | Creates a new data migration with the given title. |
| `ensurePaths` | Creates the configured file paths. |
| `icons` | Generates the stylesheet and preview page of every icon set. |
| `version` | Prints the installed version. |

Run `./framework` with no arguments to list them with their parameters.
