**laravel 支付**<br>
支持 paypal stripe<br>

环境<br>
php8.0+<br>
laravel9.0+<br>
mysql5.7+<br>

安装<br>
`composer require aphly/laravel-payment` <br>
`php artisan vendor:publish --provider="Aphly\LaravelPayment\PaymentServiceProvider"` <br>


config/logging.php<br>
channels 中添加
`'payment' => [
'driver' => 'daily',
'path' => storage_path('logs/payment.log'),
'level' => env('LOG_LEVEL', 'debug'),
'days' => 30,
],`
