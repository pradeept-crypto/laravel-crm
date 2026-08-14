<?php

return [
    'menu' => [
        'title' => 'ماژول تست',
        'dashboard' => 'داشبورد',
        'records' => 'رکوردها',
    ],

    'acl' => [
        'title' => 'ماژول تست',
        'dashboard' => 'داشبورد',
        'records' => 'رکوردها',
        'create' => 'ایجاد',
        'edit' => 'ویرایش',
        'delete' => 'حذف',
    ],

    'dashboard' => [
        'title' => 'داشبورد ماژول تست',
        'subtitle' => 'نمای کلی هتل‌ها و موقعیت‌ها در سیستم',
        'view-records-btn' => 'مشاهده رکوردها',
        'cards' => [
            'total-hotels' => 'کل هتل‌ها',
            'total-cities' => 'کل شهرها',
            'active-records' => 'رکوردهای فعال',
        ],
        'sections' => [
            'city-distribution' => 'هتل‌ها بر اساس شهر',
            'recent-hotels' => 'هتل‌های اخیراً اضافه شده',
        ],
        'empty-data' => 'هنوز هیچ رکوردی برای هتل یافت نشد.',
        'view-all' => 'مشاهده همه',
    ],

    'records' => [
        'index' => [
            'title' => 'رکوردهای هتل',
            'create-btn' => 'ایجاد هتل',
        ],

        'create' => [
            'title' => 'ایجاد هتل',
            'hotel-name' => 'نام هتل',
            'hotel-name-placeholder' => 'نام هتل را وارد کنید',
            'contact-number' => 'شماره تماس',
            'contact-number-placeholder' => 'شماره تماس را وارد کنید',
            'email' => 'ایمیل',
            'email-placeholder' => 'آدرس ایمیل را وارد کنید',
            'city' => 'شهر',
            'city-placeholder' => 'شهر را وارد کنید',
            'save-btn' => 'ذخیره هتل',
        ],

        'edit' => [
            'title' => 'ویرایش هتل',
        ],

        'datagrid' => [
            'id' => 'شناسه',
            'hotel-name' => 'نام هتل',
            'contact-number' => 'شماره تماس',
            'email' => 'ایمیل',
            'city' => 'شهر',
            'created-at' => 'تاریخ ایجاد',
            'edit' => 'ویرایش',
            'delete' => 'حذف',
        ],

        'create-success' => 'هتل با موفقیت ایجاد شد.',
        'update-success' => 'هتل با موفقیت به‌روزرسانی شد.',
        'delete-success' => 'هتل با موفقیت حذف شد.',
        'delete-failed' => 'هتل قابل حذف نیست.',
        'mass-delete-success' => 'هتل‌های انتخاب شده با موفقیت حذف شدند.',
    ],
];
