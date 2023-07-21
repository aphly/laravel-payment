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

Route::post('payment/{method_name}/notify', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@notify');

Route::middleware(['web'])->group(function () {
    Route::prefix('payment')->group(function () {
        Route::get('{method_name}/return', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@return');
        //Route::get('show', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@show');
        //Route::post('refund', 'Aphly\LaravelPayment\Controllers\Front\PaymentController@refund');
    });
});

Route::middleware(['web'])->group(function () {

    Route::prefix('payment_admin')->middleware(['managerAuth'])->group(function () {

        Route::middleware(['rbac'])->group(function () {

            $route_arr = [
                ['method','\MethodController'],['payment','\PaymentController'],['params','\ParamsController']
            ];
            foreach ($route_arr as $val){
                Route::get($val[0].'/index', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@index');
                Route::get($val[0].'/form', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@form');
                Route::post($val[0].'/save', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@save');
                Route::post($val[0].'/del', 'Aphly\LaravelPayment\Controllers\Admin'.$val[1].'@del');
            }

            Route::match(['get', 'post'],'payment/refund', 'Aphly\LaravelPayment\Controllers\Admin\PaymentController@refund');
            Route::match(['get', 'post'],'payment/show', 'Aphly\LaravelPayment\Controllers\Admin\PaymentController@show');
        });
    });

});
