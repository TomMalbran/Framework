<?php
namespace {{namespace}};

/**
 * The Email Codes
 */
enum EmailCode : string {

{{^hasEmailCodes}}
    case None = "none";
{{/hasEmailCodes}}
{{#emailCodes}}
    case {{name}} = "{{value}}";
{{/emailCodes}}

}
