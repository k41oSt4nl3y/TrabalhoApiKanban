<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Kanban API',
        'version' => '1.0'
    ]);
});
