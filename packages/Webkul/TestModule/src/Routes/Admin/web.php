<?php

use Illuminate\Support\Facades\Route;
use TestModule\Http\Controllers\DashboardController;
use TestModule\Http\Controllers\TestModuleController;

Route::prefix('testmodule')->group(function () {
    Route::get('', fn () => redirect()->route('admin.testmodule.dashboard.index'))
        ->name('admin.testmodule.index');

    Route::prefix('dashboard')->group(function () {
        Route::get('', [DashboardController::class, 'index'])
            ->name('admin.testmodule.dashboard.index');

        Route::get('stats', [DashboardController::class, 'stats'])
            ->name('admin.testmodule.dashboard.stats');
    });

    Route::prefix('records')->group(function () {
        Route::get('', [TestModuleController::class, 'index'])
            ->name('admin.testmodule.records.index');

        Route::post('', [TestModuleController::class, 'store'])
            ->name('admin.testmodule.records.store');

        Route::get('{id}/edit', [TestModuleController::class, 'edit'])
            ->name('admin.testmodule.records.edit');

        Route::put('{id}', [TestModuleController::class, 'update'])
            ->name('admin.testmodule.records.update');

        Route::delete('{id}', [TestModuleController::class, 'destroy'])
            ->name('admin.testmodule.records.destroy');

        Route::post('mass-destroy', [TestModuleController::class, 'massDestroy'])
            ->name('admin.testmodule.records.mass_delete');
    });
});
