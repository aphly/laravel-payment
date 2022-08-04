<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Libs\Helper;
use Aphly\Laravel\Mail\MailSend;
use Aphly\LaravelPayment\Controllers\Controller;

use Aphly\LaravelPayment\Services\Paypal\Client;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use function config;
use function redirect;
use Aphly\LaravelPayment\Services\Paypal\Order;

class PaypalController extends Controller
{

    public function order($request)
    {
        $order = new Order;
        $price = number_format(floatval($request->input('price',0)),2);
        if($price){
            $purchaseUnits = [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $price,
                    ],
                ],
            ];
            $applicationContext = [
                'brand_name' => env('APP_NAME'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => 'http://test2.com/return',
                'cancel_url' => 'http://test2.com/cancel',
            ];
            $pay_url = $order->create($purchaseUnits, 'CAPTURE', $applicationContext);
            return redirect($pay_url);
        }

    }

    public function return($request)
    {
        $res['title'] = 'Payment return';
        return $this->makeView('laravel-payment::front.payment.return',['res'=>$res]);
    }


}
