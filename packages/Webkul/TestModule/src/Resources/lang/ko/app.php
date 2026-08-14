<?php

return [
    'menu' => [
        'title' => '테스트 모듈',
        'dashboard' => '대시보드',
        'records' => '레코드',
    ],

    'acl' => [
        'title' => '테스트 모듈',
        'dashboard' => '대시보드',
        'records' => '레코드',
        'create' => '생성',
        'edit' => '수정',
        'delete' => '삭제',
    ],

    'dashboard' => [
        'title' => '테스트 모듈 대시보드',
        'subtitle' => '시스템 내 호텔 및 위치 현황 개요',
        'view-records-btn' => '레코드 보기',
        'cards' => [
            'total-hotels' => '총 호텔 수',
            'total-cities' => '총 도시 수',
            'active-records' => '활성 레코드',
        ],
        'sections' => [
            'city-distribution' => '도시별 호텔',
            'recent-hotels' => '최근 추가된 호텔',
        ],
        'empty-data' => '등록된 호텔 레코드가 없습니다.',
        'view-all' => '전체 보기',
    ],

    'records' => [
        'index' => [
            'title' => '호텔 레코드',
            'create-btn' => '호텔 등록',
        ],

        'create' => [
            'title' => '호텔 등록',
            'hotel-name' => '호텔 이름',
            'hotel-name-placeholder' => '호텔 이름을 입력하세요',
            'contact-number' => '연락처',
            'contact-number-placeholder' => '연락처 번호를 입력하세요',
            'email' => '이메일',
            'email-placeholder' => '이메일 주소를 입력하세요',
            'city' => '도시',
            'city-placeholder' => '도시를 입력하세요',
            'save-btn' => '호텔 저장',
        ],

        'edit' => [
            'title' => '호텔 수정',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => '호텔 이름',
            'contact-number' => '연락처',
            'email' => '이메일',
            'city' => '도시',
            'created-at' => '등록일',
            'edit' => '수정',
            'delete' => '삭제',
        ],

        'create-success' => '호텔이 성공적으로 등록되었습니다.',
        'update-success' => '호텔 정보가 성공적으로 수정되었습니다.',
        'delete-success' => '호텔이 성공적으로 삭제되었습니다.',
        'delete-failed' => '호텔을 삭제할 수 없습니다.',
        'mass-delete-success' => '선택한 호텔이 성공적으로 삭제되었습니다.',
    ],
];
