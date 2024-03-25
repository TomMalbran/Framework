<?php
namespace {{namespace}}System;
{{#hasUses}}

{{#uses}}
use {{namespace}}Schema\{{.}};{{/uses}}
{{/hasUses}}

/**
 * The Dispatcher
 */
class Dispatcher {
{{#dispatchers}}

    /**
     * Dispatches the {{event}} Event{{#params}}
     * @param {{docType}} ${{name}}{{/params}}
     * @return boolean
     */
    public static function {{event}}({{#params}}{{^isFirst}}, {{/isFirst}}{{type}} ${{name}}{{/params}}): bool {
        {{#dispatches}}
        \{{namespace}}{{.}}({{#params}}{{^isFirst}}, {{/isFirst}}${{name}}{{/params}});
        {{/dispatches}}
        return true;
    }
{{/dispatchers}}
}
