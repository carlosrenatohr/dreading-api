<?php

use App\Http\Controllers\ReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    Route::get('/readings', [ReadingController::class, 'last_reading']) // 'App\Http\Controllers\ReadingController@index'
        ->name('readings.index');
    Route::get('/readings/date/{date}', [ReadingController::class, 'from_date']);
    Route::get('/readings/last', [ReadingController::class, 'last_reading']);
    Route::get('/readings/last_month', [ReadingController::class, 'from_last_month']);
    Route::get('/readings/last_week', [ReadingController::class, 'from_last_week']);
    Route::get('/readings/last_day', [ReadingController::class, 'from_last_day']);
    Route::get('/readings/today', [ReadingController::class, 'from_today']);

});

Route::prefix('v2')->group(function() {
    Route::redirect('/readings', function() {
        return redirect()->route('readings.index');
    }, 301);
    }
);