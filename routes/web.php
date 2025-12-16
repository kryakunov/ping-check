<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;


Route::view('/', 'main')->name('main1');
Route::view('/posmotret-kogo-chelovek-dobavil-v-druzyi-vk', 'main2')->name('main2');
Route::view('/uznat-kogo-chelovek-dobavil-v-druzyi-vk', 'main3')->name('main3');
Route::view('/kak-posmotret-kogo-dobavil-drug-v-vk', 'main4')->name('main4');

Route::post('/bot', TelegramController::class)->withoutMiddleware(['web', 'csrf'])->name('bot');
Route::get('/set-webhook', [TelegramController::class, 'setWebhook']);
Route::get('/check', CronController::class)->name('check');


