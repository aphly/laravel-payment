<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Libs\Helper;
use Aphly\Laravel\Mail\MailSend;
use Aphly\LaravelPayment\Controllers\Controller;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use function config;
use function redirect;
use Aphly\LaravelPayment\Services\Paypal\Order;

class PaypalController extends Controller
{

    public function index()
    {
        $order = new Order;
        $purchaseUnits = [
            [
                'amount' => [
                    'currency_code' => 'GBP',
                    'value' => 12.50,
                ],
            ],
        ];
        $applicationContext = [
            'brand_name' => 'My Online Shop',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
            'return_url' => 'https://localhost/return',
            'cancel_url' => 'https://localhost/cancel',
        ];
        $paypalOrder = $order->create($purchaseUnits, 'CAPTURE', $applicationContext);
        dd($paypalOrder);
    }


}
