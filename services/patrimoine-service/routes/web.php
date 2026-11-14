<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'patrimoine-service',
        'status' => 'ok',
    ]);
});
