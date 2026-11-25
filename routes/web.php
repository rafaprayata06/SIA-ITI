<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/simulasi', function () {
    return view('simulasi');
});

Route::get('/api-docs/openapi-t1.yaml', function () {
    return response()->file(base_path('docs/api/openapi-t1.yaml'), ['Content-Type' => 'text/yaml; charset=UTF-8']);
});
