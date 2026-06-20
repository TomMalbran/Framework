<?php
namespace {{namespace}};

use Framework\Intl\NLS;

/**
 * The Log Internationalization
 */
class LogIntl {

    /**
     * Returns the translated name of the given Module
     * @param string $module
     * @param string $language Optional.
     * @return string
     */
    public static function getModuleName(
        string $module,
        string $language = "",
    ): string {
        if ($language === "") {
            $language = NLS::getLanguage();
        }

        return match ($language) {
        {{#languages}}
            {{{language}}} => self::get{{method}}ModuleName($module),
        {{/languages}}
            default => "",
        };
    }

    /**
     * Returns the translated name of the given Action
     * @param string $module
     * @param string $action
     * @param string $language Optional.
     * @return string
     */
    public static function getActionName(
        string $module,
        string $action,
        string $language = "",
    ): string {
        if ($language === "") {
            $language = NLS::getLanguage();
        }

        return match ($language) {
        {{#languages}}
            {{{language}}} => self::get{{method}}ActionName($module, $action),
        {{/languages}}
            default => "",
        };
    }
{{#languages}}

    /**
     * Returns the {{method}} name of the given Module
     * @param string $module
     * @return string
     */
    private static function get{{method}}ModuleName(string $module): string {
        return match (Sec::fromValue($module)) {
        {{#modules}}
            Sec::{{name}} => {{{label}}},
        {{/modules}}
            default => "",
        };
    }

    /**
     * Returns the {{method}} name of the given Action
     * @param string $module
     * @param string $action
     * @return string
     */
    private static function get{{method}}ActionName(
        string $module,
        string $action,
    ): string {
        $mod = Sec::fromValue($module);
        $act = Act::fromValue($action);

        return match ($mod) {
        {{#modules}}
            Sec::{{name}} => match ($act) {
            {{#actions}}
                Act::{{name}} => {{{label}}},
            {{/actions}}
                default => $action,
            },
        {{/modules}}
            default => $module,
        };
    }
{{/languages}}
}
