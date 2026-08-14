<?php

return [
    [
        'key' => 'testmodule',
        'name' => 'testmodule::app.menu.title',
        'route' => 'admin.testmodule.dashboard.index',
        'sort' => 3,
        'icon-class' => 'icon-settings-warehouse',
    ], [
        'key' => 'testmodule.dashboard',
        'name' => 'testmodule::app.menu.dashboard',
        'route' => 'admin.testmodule.dashboard.index',
        'sort' => 1,
        'icon-class' => '',
    ], [
        'key' => 'testmodule.records',
        'name' => 'testmodule::app.menu.records',
        'route' => 'admin.testmodule.records.index',
        'sort' => 2,
        'icon-class' => '',
    ],
];
