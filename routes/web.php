<?php

use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

Route::get('/', 'ConfigurationController@index')->middleware(['auth.shopify'])->name('home');

Route::post('/set-config', 'ConfigurationController@setConfig')->middleware(['auth.shopify'])->name('set-config');

Route::get('/set-config', 'ConfigurationController@setConfig')->middleware(['auth.shopify'])->name('set-config');

Route::get('/processing', 'PaymentController@processing')->middleware(['auth.payment'])->name('custom_checkout');

Route::get('/processed', 'PaymentController@processed')->middleware(['cors'])->name('processed');

Route::get('/success-charge', 'PaymentController@successCharge')->middleware(['auth.shopify'])->name('successCharge');

Route::get('/failed-charge', 'PaymentController@FailedCharge')->name('FailedCharge');

Route::get('/GetRefund', 'PaymentController@GetRefund')->name('GetRefund');

Route::get('/notification', 'PaymentController@PaymentNotification')->name('PaymentNotification'); 

Route::post('/notification', 'PaymentController@PaymentNotification')->name('PaymentNotification'); 

