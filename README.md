**laravel 支付**<br>
支持 paypal stripe<br>

环境<br>
php8.0+<br>
laravel9.0+<br>
mysql5.7+<br>

安装<br>
`composer require aphly/laravel-payment` <br>
`php artisan vendor:publish --provider="Aphly\LaravelPayment\PaymentServiceProvider"` <br>

stripe需要安装包<br>
`"require": {
"stripe/stripe-php": "^9.4",
}`<br>
或者 `composer require stripe/stripe-php`<br>

config/logging.php<br>
channels 中添加
`'payment' => [
'driver' => 'daily',
'path' => storage_path('logs/payment.log'),
'level' => env('LOG_LEVEL', 'debug'),
'days' => 30,
],`
