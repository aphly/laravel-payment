<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Controllers\Controller;
use Aphly\LaravelPayment\Models\Method;
use Aphly\LaravelPayment\Models\Payment;
use Illuminate\Http\Request;

class PayController extends Controller
{

    public function pay($data)
    {
        $method = Method::where('id',$data['method_id'])->where('status',1)->first();
        if(!empty($method)){
            $class = '\Aphly\LaravelPayment\Controllers\Front\\'.ucfirst($method->name).'Controller';
            if (class_exists($class)){
                (new $class)->pay($data);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        }
    }

    public function notify(Request $request)
    {
        $payment = Payment::where('transaction_id',$request->input('PayerID'))->first();
        if(!empty($payment) && $payment->status==1 && $payment->notify_func){
            list($class,$func) = explode('@',$payment->notify_func);
            if (class_exists($class) && method_exists($class,$func)){
                return (new $class)->{$func}($payment);
            }
        }
        return 'fail';
    }

    public function return(Request $request)
    {
        //payment/return?token=7U168763NS774425V&PayerID=5JBR62CD2ZXS4
        $payment = Payment::where('transaction_id',$request->query('token'))->first();
        if(!empty($payment)){
            $method = Method::where('id',$payment->method_id)->where('status',1)->first();
            if(!empty($method)){
                $class = '\Aphly\LaravelPayment\Controllers\Front\\'.ucfirst($method->name).'Controller';
                if (class_exists($class)) {
                    (new $class)->return($payment);
                }
            }
        }
    }

    public function form(Request $request)
    {
        $data['method_id'] = 1;
        $data['amount'] = 12.22;
        $data['return_url'] = 'http://test2.com/payment/return';
        $data['cancel_url'] = 'http://test2.com/payment/cancel_url';
        $data['notify_func'] = '\Aphly\LaravelPayment\Controllers\Front\PayController@t1';
        $this->pay($data);
    }
}
