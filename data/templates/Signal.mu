<?php
namespace {{codeSpace}};
{{#hasUses}}

{{#uses}}use {{nameSpace}}Schema\{{.}};
{{/uses}}
{{/hasUses}}

/**
 * The Signal
 */
class Signal {
{{#signals}}

    /**
     * Triggers the {{event}} Signal{{#params}}
     * @param {{docType}} ${{name}}{{/params}}
     * @return boolean
     */
    public static function {{event}}({{#params}}{{^isFirst}}, {{/isFirst}}{{type}} ${{name}}{{/params}}): bool {
        {{#triggers}}
        \{{nameSpace}}{{.}}({{#params}}{{^isFirst}}, {{/isFirst}}${{name}}{{/params}});
        {{/triggers}}
        return true;
    }
{{/signals}}
}
