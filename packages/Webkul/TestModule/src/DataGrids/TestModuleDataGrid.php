<?php

namespace TestModule\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class TestModuleDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('test_modules')
            ->select(
                'test_modules.id',
                'test_modules.hotel_name',
                'test_modules.contact_number',
                'test_modules.email',
                'test_modules.city',
                'test_modules.created_at',
            );

        $this->addFilter('id', 'test_modules.id');
        $this->addFilter('hotel_name', 'test_modules.hotel_name');
        $this->addFilter('contact_number', 'test_modules.contact_number');
        $this->addFilter('email', 'test_modules.email');
        $this->addFilter('city', 'test_modules.city');
        $this->addFilter('created_at', 'test_modules.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('testmodule::app.records.datagrid.id'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'hotel_name',
            'label' => trans('testmodule::app.records.datagrid.hotel-name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'contact_number',
            'label' => trans('testmodule::app.records.datagrid.contact-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'email',
            'label' => trans('testmodule::app.records.datagrid.email'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'city',
            'label' => trans('testmodule::app.records.datagrid.city'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('testmodule::app.records.datagrid.created-at'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'filterable_type' => 'date_range',
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('testmodule.records.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('testmodule::app.records.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.testmodule.records.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('testmodule.records.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('testmodule::app.records.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.testmodule.records.destroy', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('testmodule.records.delete')) {
            $this->addMassAction([
                'icon' => 'icon-delete',
                'title' => trans('testmodule::app.records.datagrid.delete'),
                'method' => 'POST',
                'url' => route('admin.testmodule.records.mass_delete'),
            ]);
        }
    }
}
