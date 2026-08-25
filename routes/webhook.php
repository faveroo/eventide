<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('webhook')->group(function() {
    Route::get('/github', function(Request $request) {

    });
});