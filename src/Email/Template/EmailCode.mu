<?php
namespace {{namespace}};

/**
 * The Email Codes
 */
enum EmailCode {

    case None;
{{#codes}}
    case {{.}};
{{/codes}}
}
