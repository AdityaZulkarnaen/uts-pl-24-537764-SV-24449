<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'Inventaris Lab API. Gunakan endpoint di bawah /api.',
    ]);
});
