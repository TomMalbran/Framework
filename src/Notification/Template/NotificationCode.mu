<?php
namespace {{namespace}};

/**
 * The Notification Codes
 */
enum NotificationCode {

    case None;

{{#codes}}
    case {{.}};
{{/codes}}

}
