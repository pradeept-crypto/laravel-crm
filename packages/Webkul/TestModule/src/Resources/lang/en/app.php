<?php

return [
    'menu' => [
        'title' => 'Test Module',
        'dashboard' => 'Dashboard',
        'records' => 'Records',
    ],

    'acl' => [
        'title' => 'Test Module',
        'dashboard' => 'Dashboard',
        'records' => 'Records',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'dashboard' => [
        'title' => 'Test Module Dashboard',
        'subtitle' => 'Overview of hotels and locations in the system',
        'view-records-btn' => 'View Records',
        'cards' => [
            'total-hotels' => 'Total Hotels',
            'total-cities' => 'Total Cities',
            'active-records' => 'Active Records',
        ],
        'sections' => [
            'city-distribution' => 'Hotels by City',
            'recent-hotels' => 'Recently Added Hotels',
        ],
        'empty-data' => 'No hotel records found yet.',
        'view-all' => 'View All',
    ],

    'records' => [
        'index' => [
            'title' => 'Hotel Records',
            'create-btn' => 'Create Hotel',
        ],

        'create' => [
            'title' => 'Create Hotel',
            'hotel-name' => 'Hotel Name',
            'hotel-name-placeholder' => 'Enter hotel name',
            'contact-number' => 'Contact Number',
            'contact-number-placeholder' => 'Enter contact number',
            'email' => 'Email',
            'email-placeholder' => 'Enter email address',
            'city' => 'City',
            'city-placeholder' => 'Enter city',
            'save-btn' => 'Save Hotel',
        ],

        'edit' => [
            'title' => 'Edit Hotel',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => 'Hotel Name',
            'contact-number' => 'Contact Number',
            'email' => 'Email',
            'city' => 'City',
            'created-at' => 'Created At',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],

        'create-success' => 'Hotel created successfully.',
        'update-success' => 'Hotel updated successfully.',
        'delete-success' => 'Hotel deleted successfully.',
        'delete-failed' => 'Hotel cannot be deleted.',
        'mass-delete-success' => 'Selected hotels deleted successfully.',
    ],
];
