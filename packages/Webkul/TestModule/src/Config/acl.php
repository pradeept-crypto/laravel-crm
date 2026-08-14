<?php

return [
    [
        'key' => 'testmodule',
        'name' => 'testmodule::app.acl.title',
        'route' => 'admin.testmodule.dashboard.index',
        'sort' => 3,
    ], [
        'key' => 'testmodule.dashboard',
        'name' => 'testmodule::app.acl.dashboard',
        'route' => 'admin.testmodule.dashboard.index',
        'sort' => 1,
    ], [
        'key' => 'testmodule.records',
        'name' => 'testmodule::app.acl.records',
        'route' => 'admin.testmodule.records.index',
        'sort' => 2,
    ], [
        'key' => 'testmodule.records.create',
        'name' => 'testmodule::app.acl.create',
        'route' => ['admin.testmodule.records.store'],
        'sort' => 1,
    ], [
        'key' => 'testmodule.records.edit',
        'name' => 'testmodule::app.acl.edit',
        'route' => ['admin.testmodule.records.edit', 'admin.testmodule.records.update'],
        'sort' => 2,
    ], [
        'key' => 'testmodule.records.delete',
        'name' => 'testmodule::app.acl.delete',
        'route' => ['admin.testmodule.records.destroy', 'admin.testmodule.records.mass_delete'],
        'sort' => 3,
    ],
];
