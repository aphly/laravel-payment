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
        if($amount){
            $purchaseUnits = [
                [
                    'amount' => [
                        'currency_code' => 'USD',
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

    public function return($request)
    {
        $payment_id = session()->get('paypal_payment_id');
        if (empty($request->query('PayerID')) || empty($request->queryget('token'))) {
            return redirect()->route('paypal-form');
        }

        $payment = Payment::get($payment_id, $this->_api_context);

        $execution = new PaymentExecution();

        $execution->setPayerId(Input::get('PayerID'));

        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() == 'approved') {
            session()->put('success','Payment success');
            return redirect()->route('paypal-form');

        }
        session()->put('error','Payment failed');
        return redirect()->route('paypal-form');
    }


}
