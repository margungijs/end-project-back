<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MigrationController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::post('/run-latest-migration', [MigrationController::class, 'runLatestMigration']);

require __DIR__.'/auth.php';
