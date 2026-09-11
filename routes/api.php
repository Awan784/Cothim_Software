<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This application has been reduced to a users-only system.
| Add API endpoints here if/when you need them.
|
*/

Route::get('/health', fn () => response()->json(['ok' => true]));
