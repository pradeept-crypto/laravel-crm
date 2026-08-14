<?php

return [
    [
        'key' => 'whatsapp',
        'name' => 'whatsapp::app.acl.title',
        'route' => 'admin.whatsapp.index',
        'sort' => 4,
    ], [
        'key' => 'whatsapp.chat',
        'name' => 'whatsapp::app.acl.chat',
        'route' => 'admin.whatsapp.chat',
        'sort' => 1,
    ], [
        'key' => 'whatsapp.send',
        'name' => 'whatsapp::app.acl.send',
        'route' => 'admin.whatsapp.send',
        'sort' => 2,
    ],
];
