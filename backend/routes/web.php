<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestTemplateController;

Route::get('/', function () {
    return view('welcome');
});

// Route for testing templates with demo data
Route::get('/test-template/{template}', [TestTemplateController::class, 'show']);
