<?php

return [
    'menu' => [
        'title' => 'Mô-đun thử nghiệm',
        'dashboard' => 'Bảng điều khiển',
        'records' => 'Hồ sơ',
    ],

    'acl' => [
        'title' => 'Mô-đun thử nghiệm',
        'dashboard' => 'Bảng điều khiển',
        'records' => 'Hồ sơ',
        'create' => 'Tạo',
        'edit' => 'Chỉnh sửa',
        'delete' => 'Xóa',
    ],

    'dashboard' => [
        'title' => 'Bảng điều khiển mô-đun thử nghiệm',
        'subtitle' => 'Tổng quan về khách sạn và vị trí trong hệ thống',
        'view-records-btn' => 'Xem hồ sơ',
        'cards' => [
            'total-hotels' => 'Tổng số khách sạn',
            'total-cities' => 'Tổng số thành phố',
            'active-records' => 'Hồ sơ đang hoạt động',
        ],
        'sections' => [
            'city-distribution' => 'Khách sạn theo thành phố',
            'recent-hotels' => 'Khách sạn mới thêm gần đây',
        ],
        'empty-data' => 'Chưa tìm thấy hồ sơ khách sạn nào.',
        'view-all' => 'Xem tất cả',
    ],

    'records' => [
        'index' => [
            'title' => 'Hồ sơ khách sạn',
            'create-btn' => 'Tạo khách sạn',
        ],

        'create' => [
            'title' => 'Tạo khách sạn',
            'hotel-name' => 'Tên khách sạn',
            'hotel-name-placeholder' => 'Nhập tên khách sạn',
            'contact-number' => 'Số liên lạc',
            'contact-number-placeholder' => 'Nhập số liên lạc',
            'email' => 'Email',
            'email-placeholder' => 'Nhập địa chỉ email',
            'city' => 'Thành phố',
            'city-placeholder' => 'Nhập thành phố',
            'save-btn' => 'Lưu khách sạn',
        ],

        'edit' => [
            'title' => 'Chỉnh sửa khách sạn',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => 'Tên khách sạn',
            'contact-number' => 'Số liên lạc',
            'email' => 'Email',
            'city' => 'Thành phố',
            'created-at' => 'Ngày tạo',
            'edit' => 'Chỉnh sửa',
            'delete' => 'Xóa',
        ],

        'create-success' => 'Tạo khách sạn thành công.',
        'update-success' => 'Cập nhật khách sạn thành công.',
        'delete-success' => 'Xóa khách sạn thành công.',
        'delete-failed' => 'Không thể xóa khách sạn.',
        'mass-delete-success' => 'Đã xóa thành công các khách sạn đã chọn.',
    ],
];
