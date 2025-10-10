<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;


Route::get('/', function(){
    dd(file_get_contents('log.txt'));
});
Route::post('/bot', TelegramController::class)->withoutMiddleware(['web', 'csrf'])->name('bot');
Route::get('/set-webhook', [TelegramController::class, 'setWebhook']);
Route::get('/check', CronController::class)->name('check');


