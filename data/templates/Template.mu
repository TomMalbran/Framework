<?php
namespace {{namespace}};

/**
 * The Templates
 */
enum Template : string {

{{#templates}}
    case {{name}} = "{{value}}";
{{/templates}}

}
