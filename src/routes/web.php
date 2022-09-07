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

Route::match(['get','post'],'test/index', 'Aphly\LaravelPayment\Controllers\Front\TestController@index');
Route::match(['post'],'test/notify', 'Aphly\LaravelPayment\Controllers\Front\TestController@notify');

Route::post('payment/notify/{method_name}', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@notify');

Route::middleware(['web'])->group(function () {
    Route::prefix('payment')->group(function () {
        Route::get('return/{method_name}', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@return');
        Route::get('show', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@show');
    });
});

Route::middleware(['web'])->group(function () {

    Route::prefix('payment_admin')->middleware(['managerAuth'])->group(function () {

        Route::middleware(['rbac'])->group(function () {

            Route::post('/method/install', 'Aphly\LaravelPayment\Controllers\Admin\MethodController@del');

            $route_arr = [
                ['method','\MethodController'],['payment','\PaymentController'],['params','\ParamsController']
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
