<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\LogController;

Route::get('/', [ChatController::class, 'index']);

Route::get('/admin', 'App\Http\Controllers\AdminController@index');
Route::get('/admin/show', 'App\Http\Controllers\AdminController@show');
Route::post('/reset', 'App\Http\Controllers\AdminController@reset');

Route::post('/chat', 'App\Http\Controllers\ChatController@chat');

Route::get('/data', [DataController::class, 'index'])->name('data.index');
Route::get('/data/index/current', [DataController::class, 'current'])->name('data.current');
Route::get('/data/index/drop', [DataController::class, 'drop'])->name('data.drop');
Route::post('/data/upload', [DataController::class, 'upload'])->name('data.upload');
Route::get('/data/create', [DataController::class, 'create'])->name('data.create');
Route::get('/data/remove', [DataController::class, 'remove'])->name('data.remove');

Route::get('/cache', [CacheController::class, 'index'])->name('cache.index');
Route::get('/cache/delete', [CacheController::class, 'delete'])->name('cache.delete');
Route::post('/cache/save', [CacheController::class, 'save'])->name('cache.save');

Route::get('/prompt', [PromptController::class, 'index'])->name('prompt.index');
Route::post('/prompt/save', [PromptController::class, 'save'])->name('prompt.save');

Route::get('/logger', [LogController::class, 'index']);

Route::get('/debug-env', function () {
    return response()->json([
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD'),
    ]);
});
