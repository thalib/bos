<?php

use App\Http\Controllers\TestTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route for testing templates with demo data
Route::get('/test-template/{template}', [TestTemplateController::class, 'show']);
