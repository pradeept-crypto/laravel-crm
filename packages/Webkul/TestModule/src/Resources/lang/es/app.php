<?php

return [
    'menu' => [
        'title' => 'Módulo de Prueba',
        'dashboard' => 'Panel',
        'records' => 'Registros',
    ],

    'acl' => [
        'title' => 'Módulo de Prueba',
        'dashboard' => 'Panel',
        'records' => 'Registros',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
    ],

    'dashboard' => [
        'title' => 'Panel del Módulo de Prueba',
        'subtitle' => 'Resumen de hoteles y ubicaciones en el sistema',
        'view-records-btn' => 'Ver Registros',
        'cards' => [
            'total-hotels' => 'Total de Hoteles',
            'total-cities' => 'Total de Ciudades',
            'active-records' => 'Registros Activos',
        ],
        'sections' => [
            'city-distribution' => 'Hoteles por Ciudad',
            'recent-hotels' => 'Hoteles Agregados Recientemente',
        ],
        'empty-data' => 'Aún no se encontraron registros de hoteles.',
        'view-all' => 'Ver Todo',
    ],

    'records' => [
        'index' => [
            'title' => 'Registros de Hoteles',
            'create-btn' => 'Crear Hotel',
        ],

        'create' => [
            'title' => 'Crear Hotel',
            'hotel-name' => 'Nombre del Hotel',
            'hotel-name-placeholder' => 'Ingrese el nombre del hotel',
            'contact-number' => 'Número de Contacto',
            'contact-number-placeholder' => 'Ingrese el número de contacto',
            'email' => 'Correo Electrónico',
            'email-placeholder' => 'Ingrese el correo electrónico',
            'city' => 'Ciudad',
            'city-placeholder' => 'Ingrese la ciudad',
            'save-btn' => 'Guardar Hotel',
        ],

        'edit' => [
            'title' => 'Editar Hotel',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => 'Nombre del Hotel',
            'contact-number' => 'Número de Contacto',
            'email' => 'Correo Electrónico',
            'city' => 'Ciudad',
            'created-at' => 'Fecha de Creación',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
        ],

        'create-success' => 'Hotel creado exitosamente.',
        'update-success' => 'Hotel actualizado exitosamente.',
        'delete-success' => 'Hotel eliminado exitosamente.',
        'delete-failed' => 'No se puede eliminar el hotel.',
        'mass-delete-success' => 'Hoteles seleccionados eliminados exitosamente.',
    ],
];
