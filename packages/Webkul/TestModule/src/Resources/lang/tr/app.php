<?php

return [
    'menu' => [
        'title' => 'Test Modülü',
        'dashboard' => 'Kontrol Paneli',
        'records' => 'Kayıtlar',
    ],

    'acl' => [
        'title' => 'Test Modülü',
        'dashboard' => 'Kontrol Paneli',
        'records' => 'Kayıtlar',
        'create' => 'Oluştur',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
    ],

    'dashboard' => [
        'title' => 'Test Modülü Kontrol Paneli',
        'subtitle' => 'Sistemdeki oteller ve konumlara genel bakış',
        'view-records-btn' => 'Kayıtları Görüntüle',
        'cards' => [
            'total-hotels' => 'Toplam Otel',
            'total-cities' => 'Toplam Şehir',
            'active-records' => 'Aktif Kayıtlar',
        ],
        'sections' => [
            'city-distribution' => 'Şehirlere Göre Oteller',
            'recent-hotels' => 'Son Eklenen Oteller',
        ],
        'empty-data' => 'Henüz otel kaydı bulunamadı.',
        'view-all' => 'Tümünü Gör',
    ],

    'records' => [
        'index' => [
            'title' => 'Otel Kayıtları',
            'create-btn' => 'Otel Oluştur',
        ],

        'create' => [
            'title' => 'Otel Oluştur',
            'hotel-name' => 'Otel Adı',
            'hotel-name-placeholder' => 'Otel adını girin',
            'contact-number' => 'İletişim Numarası',
            'contact-number-placeholder' => 'İletişim numarasını girin',
            'email' => 'E-posta',
            'email-placeholder' => 'E-posta adresini girin',
            'city' => 'Şehir',
            'city-placeholder' => 'Şehri girin',
            'save-btn' => 'Oteli Kaydet',
        ],

        'edit' => [
            'title' => 'Oteli Düzenle',
        ],

        'datagrid' => [
            'id' => 'Kimlik',
            'hotel-name' => 'Otel Adı',
            'contact-number' => 'İletişim Numarası',
            'email' => 'E-posta',
            'city' => 'Şehir',
            'created-at' => 'Oluşturulma Tarihi',
            'edit' => 'Düzenle',
            'delete' => 'Sil',
        ],

        'create-success' => 'Otel başarıyla oluşturuldu.',
        'update-success' => 'Otel başarıyla güncellendi.',
        'delete-success' => 'Otel başarıyla silindi.',
        'delete-failed' => 'Otel silinemedi.',
        'mass-delete-success' => 'Seçilen oteller başarıyla silindi.',
    ],
];
