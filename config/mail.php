<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => getenv('MAIL_MAILER') ?: env('MAIL_MAILER', 'brevo'),

    'mailers' => [
        'brevo' => [
            'transport' => 'brevo',
        ],

        'smtp' => [
            'transport' => 'smtp',
            'host' => getenv('MAIL_HOST') ?: env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => getenv('MAIL_PORT') ?: env('MAIL_PORT', 587),
            'encryption' => getenv('MAIL_ENCRYPTION') ?: env('MAIL_ENCRYPTION', 'tls'),
            'username' => getenv('MAIL_USERNAME') ?: env('MAIL_USERNAME'),
            'password' => getenv('MAIL_PASSWORD') ?: env('MAIL_PASSWORD'),
            'timeout' => (int) (getenv('MAIL_TIMEOUT') ?: env('MAIL_TIMEOUT', 15)),
            'verify_peer' => false,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: env('MAIL_FROM_ADDRESS', 'pradeep.t@kaditinnovations.com'),
        'name' => getenv('MAIL_FROM_NAME') ?: env('MAIL_FROM_NAME', 'AUURA CRM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Mailer Domain
    |--------------------------------------------------------------------------
    |
    | This option controls the domain for email message_id that is used to send email
    | messages sent by your application.
    |
    */

    'domain' => env('MAIL_DOMAIN', 'webkul.com'),

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
