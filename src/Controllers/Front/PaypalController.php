<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Controllers\Controller;
use Aphly\LaravelPayment\Models\Payment;

use Aphly\LaravelPayment\Services\Paypal\Order;

class PaypalController extends Controller
{

    public function pay($data)
    {
        $order = new Order;
        $amount = number_format(floatval($data['amount']),2);
        $data['currency_code'] = $data['currency_code']??'USD';
        if($amount){
            $purchaseUnits = [
                [
                    'amount' => [
                        'currency_code' => $data['currency_code'],
                        'value' => $amount,
                    ],
                ],
            ];
            $applicationContext = [
                'brand_name' => env('APP_NAME'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $data['return_url'],
                'cancel_url' => $data['cancel_url'],
            ];
            $res_arr = $order->create($purchaseUnits, 'CAPTURE', $applicationContext);
            $pay_url = $order->getLinkByRel($res_arr['links'],'approve');
            $data['transaction_id'] = $res_arr['id'];
            Payment::create($data);
            redirect($pay_url)->send();
        }
    }

    public function return($payment)
    {
        $order = new Order;
        $info = $order->capture($payment->transaction_id);
        //$info = $order->show($payment->transaction_id);
        dd($info);
    }


}
