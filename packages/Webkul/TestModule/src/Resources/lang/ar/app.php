<?php

return [
    'menu' => [
        'title' => 'وحدة الاختبار',
        'dashboard' => 'لوحة التحكم',
        'records' => 'السجلات',
    ],

    'acl' => [
        'title' => 'وحدة الاختبار',
        'dashboard' => 'لوحة التحكم',
        'records' => 'السجلات',
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'delete' => 'حذف',
    ],

    'dashboard' => [
        'title' => 'لوحة تحكم وحدة الاختبار',
        'subtitle' => 'نظرة عامة على الفنادق والمواقع في النظام',
        'view-records-btn' => 'عرض السجلات',
        'cards' => [
            'total-hotels' => 'إجمالي الفنادق',
            'total-cities' => 'إجمالي المدن',
            'active-records' => 'السجلات النشطة',
        ],
        'sections' => [
            'city-distribution' => 'الفنادق حسب المدينة',
            'recent-hotels' => 'الفنادق المضافة حديثاً',
        ],
        'empty-data' => 'لا توجد سجلات فنادق حتى الآن.',
        'view-all' => 'عرض الكل',
    ],

    'records' => [
        'index' => [
            'title' => 'سجلات الفنادق',
            'create-btn' => 'إنشاء فندق',
        ],

        'create' => [
            'title' => 'إنشاء فندق',
            'hotel-name' => 'اسم الفندق',
            'hotel-name-placeholder' => 'أدخل اسم الفندق',
            'contact-number' => 'رقم الاتصال',
            'contact-number-placeholder' => 'أدخل رقم الاتصال',
            'email' => 'البريد الإلكتروني',
            'email-placeholder' => 'أدخل البريد الإلكتروني',
            'city' => 'المدينة',
            'city-placeholder' => 'أدخل المدينة',
            'save-btn' => 'حفظ الفندق',
        ],

        'edit' => [
            'title' => 'تعديل الفندق',
        ],

        'datagrid' => [
            'id' => 'المعرف',
            'hotel-name' => 'اسم الفندق',
            'contact-number' => 'رقم الاتصال',
            'email' => 'البريد الإلكتروني',
            'city' => 'المدينة',
            'created-at' => 'تاريخ الإنشاء',
            'edit' => 'تعديل',
            'delete' => 'حذف',
        ],

        'create-success' => 'تم إنشاء الفندق بنجاح.',
        'update-success' => 'تم تحديث الفندق بنجاح.',
        'delete-success' => 'تم حذف الفندق بنجاح.',
        'delete-failed' => 'تعذر حذف الفندق.',
        'mass-delete-success' => 'تم حذف الفنادق المحددة بنجاح.',
    ],
];
