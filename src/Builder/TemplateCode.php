<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use Framework\System\Language;
use Framework\System\Package;

/**
 * The Template Code
 */
class TemplateCode {

    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        $languages = Language::getAll();
        $data      = [];

        foreach ($languages as $language => $languageName) {
            /** @var array<string,string>[] */
            $data = Discovery::loadJSON(Package::EmailsDir, $language);

            if (!Arrays::isEmpty($data)) {
                break;
            }
        }

        $templates = [];
        $maxLength = 0;
        foreach ($data as $templateCode => $template) {
            $templates[] = [
                "name"  => Strings::upperCaseFirst($templateCode),
                "value" => $templateCode,
            ];
            $maxLength = max($maxLength, Strings::length($templateCode));
        }

        foreach ($templates as $index => $template) {
            $templates[$index]["name"] = Strings::padRight($template["name"], $maxLength);
        }

        return [
            "hasTemplates" => count($templates) > 0,
            "templates"    => $templates,
        ];
    }
}
