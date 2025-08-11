<?php
namespace {{namespace}};

/**
 * The Templates
 */
enum Template : string {

{{^hasTemplates}}
    case None = "none";
{{/hasTemplates}}
{{#templates}}
    case {{name}} = "{{value}}";
{{/templates}}

}
