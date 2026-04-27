<?php

namespace Aphly\LaravelPayment;

use Aphly\Laravel\Providers\ServiceProvider;
use Aphly\LaravelPayment\Commands\Init;
use Aphly\LaravelPayment\Commands\PaymentInit;

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
        $this->publishes([
            __DIR__.'/config/payment.php' => config_path('payment.php'),
            __DIR__.'/public' => public_path('static/payment')
        ]);
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadViewsFrom(__DIR__.'/views', 'laravel-payment');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Init::class,
            ]);
        }
    }

}
