<?php

return [
    'menu' => [
        'title' => 'Módulo de Teste',
        'dashboard' => 'Painel',
        'records' => 'Registros',
    ],

    'acl' => [
        'title' => 'Módulo de Teste',
        'dashboard' => 'Painel',
        'records' => 'Registros',
        'create' => 'Criar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
    ],

    'dashboard' => [
        'title' => 'Painel do Módulo de Teste',
        'subtitle' => 'Visão geral dos hotéis e localizações no sistema',
        'view-records-btn' => 'Ver Registros',
        'cards' => [
            'total-hotels' => 'Total de Hotéis',
            'total-cities' => 'Total de Cidades',
            'active-records' => 'Registros Ativos',
        ],
        'sections' => [
            'city-distribution' => 'Hotéis por Cidade',
            'recent-hotels' => 'Hotéis Adicionados Recentemente',
        ],
        'empty-data' => 'Nenhum registro de hotel encontrado.',
        'view-all' => 'Ver Todos',
    ],

    'records' => [
        'index' => [
            'title' => 'Registros de Hotéis',
            'create-btn' => 'Criar Hotel',
        ],

        'create' => [
            'title' => 'Criar Hotel',
            'hotel-name' => 'Nome do Hotel',
            'hotel-name-placeholder' => 'Digite o nome do hotel',
            'contact-number' => 'Número de Contato',
            'contact-number-placeholder' => 'Digite o número de contato',
            'email' => 'E-mail',
            'email-placeholder' => 'Digite o e-mail',
            'city' => 'Cidade',
            'city-placeholder' => 'Digite a cidade',
            'save-btn' => 'Salvar Hotel',
        ],

        'edit' => [
            'title' => 'Editar Hotel',
        ],

        'datagrid' => [
            'id' => 'ID',
            'hotel-name' => 'Nome do Hotel',
            'contact-number' => 'Número de Contato',
            'email' => 'E-mail',
            'city' => 'Cidade',
            'created-at' => 'Criado em',
            'edit' => 'Editar',
            'delete' => 'Excluir',
        ],

        'create-success' => 'Hotel criado com sucesso.',
        'update-success' => 'Hotel atualizado com sucesso.',
        'delete-success' => 'Hotel excluído com sucesso.',
        'delete-failed' => 'Não é possível excluir o hotel.',
        'mass-delete-success' => 'Hotéis selecionados excluídos com sucesso.',
    ],
];
