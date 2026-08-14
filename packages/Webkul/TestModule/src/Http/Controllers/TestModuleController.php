<?php

namespace TestModule\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use TestModule\DataGrids\TestModuleDataGrid;
use TestModule\Repositories\TestModuleRepository;

class TestModuleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TestModuleRepository $testModuleRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(TestModuleDataGrid::class)->process();
        }

        return view('testmodule::records.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        request()->validate([
            'hotel_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'city' => 'required|string|max:255',
        ]);

        Event::dispatch('testmodule.record.create.before');

        $record = $this->testModuleRepository->create(request()->only([
            'hotel_name',
            'contact_number',
            'email',
            'city',
        ]));

        Event::dispatch('testmodule.record.create.after', $record);

        return new JsonResponse([
            'data' => $record,
            'message' => trans('testmodule::app.records.create-success'),
        ], 200);
    }

    /**
     * Show the specified resource.
     */
    public function edit(int $id): JsonResponse
    {
        $record = $this->testModuleRepository->findOrFail($id);

        return new JsonResponse([
            'data' => $record,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): JsonResponse
    {
        request()->validate([
            'hotel_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'city' => 'required|string|max:255',
        ]);

        Event::dispatch('testmodule.record.update.before', $id);

        $record = $this->testModuleRepository->update(request()->only([
            'hotel_name',
            'contact_number',
            'email',
            'city',
        ]), $id);

        Event::dispatch('testmodule.record.update.after', $record);

        return new JsonResponse([
            'data' => $record,
            'message' => trans('testmodule::app.records.update-success'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Event::dispatch('testmodule.record.delete.before', $id);

            $this->testModuleRepository->delete($id);

            Event::dispatch('testmodule.record.delete.after', $id);

            return new JsonResponse([
                'message' => trans('testmodule::app.records.delete-success'),
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'message' => trans('testmodule::app.records.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass delete the specified resources.
     */
    public function massDestroy(): JsonResponse
    {
        $indices = request('indices', []);

        try {
            foreach ($indices as $id) {
                Event::dispatch('testmodule.record.delete.before', $id);

                $this->testModuleRepository->delete($id);

                Event::dispatch('testmodule.record.delete.after', $id);
            }

            return new JsonResponse([
                'message' => trans('testmodule::app.records.mass-delete-success'),
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
