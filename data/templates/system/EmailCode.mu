<?php
namespace {{namespace}};

/**
 * The Email Codes
 */
enum EmailCode : string {

    case None = "none";

{{#emailCodes}}
    case {{name}} = "{{value}}";
{{/emailCodes}}

}
