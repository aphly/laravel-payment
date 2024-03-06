<?php

namespace Aphly\LaravelPayment;

use Aphly\Laravel\Models\Comm;
use Aphly\Laravel\Providers\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */

    public function register()
    {
		$this->mergeConfigFrom(
            __DIR__.'/config/payment.php', 'payment'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $comm_module= (new Comm)->moduleClass();
        if(in_array('Aphly\LaravelPayment',$comm_module)) {
            $this->publishes([
                __DIR__ . '/config/payment.php' => config_path('payment.php'),
                __DIR__ . '/public' => public_path('static/payment')
            ]);
            //$this->loadMigrationsFrom(__DIR__.'/migrations');
            $this->loadViewsFrom(__DIR__ . '/views', 'laravel-payment');
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }
    }

}
