<?php

return [
    'menu' => [
        'title' => '测试模块',
        'dashboard' => '仪表板',
        'records' => '记录',
    ],

    'acl' => [
        'title' => '测试模块',
        'dashboard' => '仪表板',
        'records' => '记录',
        'create' => '创建',
        'edit' => '编辑',
        'delete' => '删除',
    ],

    'dashboard' => [
        'title' => '测试模块仪表板',
        'subtitle' => '系统中酒店和位置的概览',
        'view-records-btn' => '查看记录',
        'cards' => [
            'total-hotels' => '酒店总数',
            'total-cities' => '城市总数',
            'active-records' => '有效记录',
        ],
        'sections' => [
            'city-distribution' => '按城市划分的酒店',
            'recent-hotels' => '最近添加的酒店',
        ],
        'empty-data' => '暂无酒店记录。',
        'view-all' => '查看全部',
    ],

    'records' => [
        'index' => [
            'title' => '酒店记录',
            'create-btn' => '创建酒店',
        ],

        'create' => [
            'title' => '创建酒店',
            'hotel-name' => '酒店名称',
            'hotel-name-placeholder' => '请输入酒店名称',
            'contact-number' => '联系电话',
            'contact-number-placeholder' => '请输入联系电话',
            'email' => '电子邮件',
            'email-placeholder' => '请输入电子邮件地址',
            'city' => '城市',
            'city-placeholder' => '请输入城市',
            'save-btn' => '保存酒店',
        ],

        'edit' => [
            'title' => '编辑酒店',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => '酒店名称',
            'contact-number' => '联系电话',
            'email' => '电子邮件',
            'city' => '城市',
            'created-at' => '创建时间',
            'edit' => '编辑',
            'delete' => '删除',
        ],

        'create-success' => '酒店创建成功。',
        'update-success' => '酒店更新成功。',
        'delete-success' => '酒店删除成功。',
        'delete-failed' => '无法删除酒店。',
        'mass-delete-success' => '已成功删除所选酒店。',
    ],
];
