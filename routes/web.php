<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Route to serve HTML files from storage/exports
Route::get('storage/exports/{path}', function ($path) {
    // Ensure path ends with .html
    if (!str_ends_with($path, '.html')) {
        abort(404);
    }

    $filePath = storage_path('app/public/exports/' . $path);

    if (file_exists($filePath)) {
        return response()->file($filePath, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }

    abort(404);
})->where('path', '.*');