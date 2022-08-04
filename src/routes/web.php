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
        Route::get('form', 'Aphly\LaravelPayment\Controllers\Front\PayController@form');

        Route::post('order', 'Aphly\LaravelPayment\Controllers\Front\PayController@order');
        Route::post('notify', 'Aphly\LaravelPayment\Controllers\Front\PayController@notify');
        Route::get('return', 'Aphly\LaravelPayment\Controllers\Front\PayController@return');
    });
});

Route::get('/test', function (){
    $price = number_format(floatval(1.02551),2);
    dd($price);
});


Route::middleware(['web'])->group(function () {

    Route::prefix('payment_admin')->middleware(['managerAuth'])->group(function () {

        Route::middleware(['rbac'])->group(function () {

            Route::post('/method/install', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@del');

            $route_arr = [
                ['method','\MethodController']
            ];
            foreach ($route_arr as $val){
                Route::get('/'.$val[0].'/index', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@index');
                Route::get('/'.$val[0].'/form', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@form');
                Route::post('/'.$val[0].'/save', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@save');
                Route::post('/'.$val[0].'/del', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@del');
            }

            Route::get('/method_params/index', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@paramsIndex');
            Route::get('/method_params/form', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@paramsForm');
            Route::post('/method_params/save', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@paramsSave');
            Route::post('/method_params/del', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@paramsDel');

        });
    });

});
