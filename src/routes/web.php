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

Route::middleware(['web'])->group(function () {

    Route::prefix('payment')->group(function () {
        Route::get('paypal/pay', 'Aphly\LaravelPayment\Controllers\Front\PaypalController@index');

        Route::get('paypal-form', 'Payment\PayPalController@payPalShow');
        Route::post('paypal-pay', 'Payment\PayPalController@pay');
        Route::post('paypal-notify', 'Payment\PayPalController@payPalNotify');
        Route::get('paypal-return', 'Payment\PayPalController@payPalReturn');
        Route::get('paypal-cancel', 'Payment\PayPalController@payPalCancel');
    });

});

Route::get('/test', function (){

});


Route::middleware(['web'])->group(function () {

    Route::prefix('payment_admin')->middleware(['managerAuth'])->group(function () {

        Route::middleware(['rbac'])->group(function () {

            Route::post('/method/install', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@del');

            $route_arr = [
                ['method','\MethodController'],['setting','\SettingController']
            ];
            foreach ($route_arr as $val){
                Route::get('/'.$val[0].'/index', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@index');
                Route::get('/'.$val[0].'/form', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@form');
                Route::post('/'.$val[0].'/save', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@save');
                Route::post('/'.$val[0].'/del', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@del');
            }

        });
    });

});
