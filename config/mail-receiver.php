<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mail Receiver
    |--------------------------------------------------------------------------
    |
    | This option controls the default mail receiver that is used to receive any email
    | messages sent by third party application.
    |
    | Supported: "webklex-imap", "sendgrid"
    |
    */

    'default' => getenv('MAIL_RECEIVER_DRIVER') ?: env('MAIL_RECEIVER_DRIVER', 'webklex-imap'),
];
