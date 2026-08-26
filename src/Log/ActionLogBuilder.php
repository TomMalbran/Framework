<?php
namespace Framework\Log;

use Framework\Analysis\Attr\NotTested;
use Framework\Builder\Builder;
use Framework\Discovery\Discovery;
use Framework\Discovery\Package;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Type\DiscoveryClass;
use Framework\File\Storage;
use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;
use Framework\Utils\Strings;

/**
 * The Action Log Builder
 * @phpstan-type ActionData array{
 *   name:         string,
 *   label:        string,
 *   translations: array<string,string>,
 * }
 * @phpstan-type SectionData array{
 *   name:         string,
 *   label:        string,
 *   translations: array<string,string>,
 *   actions:      array<string,ActionData>,
 * }
 * @phpstan-type LanguageData array{
 *   key:      string,
 *   language: string,
 *   method:   string,
 * }
 * @phpstan-type IntlActionData array{
 *   name:  string,
 *   label: string,
 * }
 * @phpstan-type IntlSectionData array{
 *   name:    string,
 *   label:   string,
 *   actions: list<IntlActionData>,
 * }
 * @phpstan-type IntlLanguageData array{
 *   language: string,
 *   method:   string,
 *   sections: list<IntlSectionData>,
 * }
 * @phpstan-type ActionLogResult array{
 *   actions:   list<string>,
 *   sections:  list<string>,
 *   modules:   list<array{name:string,label:string}>,
 *   languages: list<IntlLanguageData>,
 *   total:     int,
 * }
 */
class ActionLogBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    #[NotTested("It generates a file")]
    public static function generateCode(): int {
        $data      = self::collectSections(Discovery::findClasses());
        $namespace = Package::Namespace . "Log\\Type";

        $created  = self::generateEnumCode("Section", [
            "namespace" => $namespace,
            "items"     => $data["sections"],
            "total"     => count($data["sections"]),
        ]);
        $created += self::generateEnumCode("Action", [
            "namespace" => $namespace,
            "items"     => $data["actions"],
            "total"     => count($data["actions"]),
        ]);
        $created += self::generateTypeCode("LogIntl", [
            "namespace" => $namespace,
            "modules"   => $data["modules"],
            "languages" => $data["languages"],
            "total"     => $data["total"],
        ]);

        return $created;
    }

    /**
     * Collects the Sections and their Actions from the given Classes
     * @param list<DiscoveryClass> $classes
     * @return ActionLogResult
     */
    public static function collectSections(array $classes): array {
        $actions   = [];
        $languages = [];
        $sections  = [];

        foreach ($classes as $class) {
            $attribute = $class->getAttribute(Section::class);
            if ($attribute === null) {
                continue;
            }

            /** @var Section */
            $section     = $attribute->newInstance();
            $sectionName = Strings::toPascalCase($section->name);
            if ($sectionName === "") {
                continue;
            }

            $translations = [];
            foreach ($section->translations as $language => $translation) {
                if ($language === "") {
                    continue;
                }

                $languages[$language] = [
                    "key"      => $language,
                    "language" => "\"$language\"",
                    "method"   => Strings::toPascalCase($language),
                ];
                $translations[$language] = "\"$translation\"";
            }

            $sections[$sectionName] = [
                "name"         => $sectionName,
                "label"        => "\"{$section->name}\"",
                "translations" => $translations,
                "actions"      => [],
            ];

            foreach ($class->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $class->getName()) {
                    continue;
                }

                $actAttributes = $method->getAttributes(Action::class);
                foreach ($actAttributes as $actAttribute) {
                    $action = $actAttribute->newInstance();
                    $actionName = Strings::toPascalCase($action->name);
                    if ($actionName === "") {
                        continue;
                    }

                    $actionTranslations = [];
                    foreach ($action->translations as $language => $translation) {
                        if ($language === "") {
                            continue;
                        }

                        $languages[$language] = [
                            "key"      => $language,
                            "language" => "\"$language\"",
                            "method"   => Strings::toPascalCase($language),
                        ];
                        $actionTranslations[$language] = "\"$translation\"";
                    }

                    $actions[$actionName] = $actionName;
                    $sections[$sectionName]["actions"][$actionName] = [
                        "name"         => $actionName,
                        "label"        => "\"{$action->name}\"",
                        "translations" => $actionTranslations,
                    ];
                }
            }
        }


        // Sort and get the Actions
        ksort($actions);
        $actionNames = array_values($actions);
        if (count($actionNames) === 0) {
            $actionNames[] = "Example";
        }

        // Sort and get the Sections
        ksort($sections);
        $sectionList  = array_values($sections);
        $sectionNames = array_keys($sections);
        if (count($sectionNames) === 0) {
            $sectionNames[] = "Example";
        }

        // Sort and get the Languages
        ksort($languages);
        $languages = array_values($languages);

        return [
            "actions"   => $actionNames,
            "sections"  => $sectionNames,
            "modules"   => self::getModuleNames($sectionList),
            "languages" => self::getModuleTranslations($languages, $sectionList),
            "total"     => count($sectionList),
        ];
    }

    /**
     * Generates a Log Type enum
     * @param string              $name
     * @param array<string,mixed> $data
     * @return int
     */
    private static function generateEnumCode(string $name, array $data): int {
        $enumName = Strings::substring($name, 0, 3);
        $path     = Package::getSourcePath("Log", "Type");
        $contents = Builder::render($enumName, $data);

        Storage::createDir($path);
        Storage::createFile($path, "$enumName.php", $contents);

        Builder::printResult($name, $data);
        return 1;
    }

    /**
     * Generates a Log Type code file
     * @param string              $name
     * @param array<string,mixed> $data
     * @return int
     */
    private static function generateTypeCode(string $name, array $data): int {
        $path     = Package::getSourcePath("Log", "Type");
        $contents = Builder::render($name, $data);

        Storage::createDir($path);
        Storage::createFile($path, "$name.php", $contents);

        Builder::printResult($name, $data);
        return 1;
    }

    /**
     * Returns Module names for the LogIntl code
     * @param list<SectionData> $sections
     * @return list<array{name:string,label:string}>
     */
    private static function getModuleNames(array $sections): array {
        $result = [];
        foreach ($sections as $section) {
            $result[] = [
                "name"  => $section["name"],
                "label" => $section["label"],
            ];
        }
        return $result;
    }

    /**
     * Returns Module translations for the LogIntl code
     * @param list<LanguageData> $languages
     * @param list<SectionData>  $sections
     * @return list<IntlLanguageData>
     */
    private static function getModuleTranslations(
        array $languages,
        array $sections,
    ): array {
        $result = [];
        foreach ($languages as $language) {
            $languageSections = [];

            foreach ($sections as $section) {
                $sectionActions = [];
                foreach ($section["actions"] as $action) {
                    $sectionActions[] = [
                        "name"  => $action["name"],
                        "label" => $action["translations"][$language["key"]] ?? "\"\"",
                    ];
                }

                $languageSections[] = [
                    "name"    => $section["name"],
                    "label"   => $section["translations"][$language["key"]] ?? "\"\"",
                    "actions" => $sectionActions,
                ];
            }

            $result[] = [
                "language" => $language["language"],
                "method"   => $language["method"],
                "sections" => $languageSections,
            ];
        }
        return $result;
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    #[NotTested("It generates a file")]
    public static function destroyCode(): int {
        $path    = Package::getSourcePath("Log", "Type");
        $deleted = 0;
        Storage::deleteDir($path, $deleted);
        return $deleted;
    }
}
