<?php

use Illuminate\Support\Facades\Route;
use TestModule\Http\Controllers\TestModuleController;

Route::middleware('admin')->prefix('testmodule')->group(function () {
    Route::get('', [TestModuleController::class, 'index'])
        ->name('admin.testmodule.index');
});
