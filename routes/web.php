<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return response()->json(['message' => 'Laravel is working!']);
});

Route::get('/test-didit', function () {
    return [
        'api_key_exists' => !empty(config('didit.api_key')),
        'api_key_length' => strlen(config('didit.api_key') ?? ''),
        'base_url' => config('didit.base_url'),
    ];
});