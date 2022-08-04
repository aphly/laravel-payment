<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Controllers\Controller;
use Aphly\LaravelPayment\Models\Method;
use Illuminate\Http\Request;
use function redirect;

class PayController extends Controller
{

    public function order(Request $request)
    {
        $payment_id = $request->input('payment_id',1);
        $method = Method::where('id',$payment_id)->where('status',1)->first();
        if(!empty($method)){
            $class = '\Aphly\LaravelPayment\Controllers\Front\\'.ucfirst($method->name).'Controller';
            if (class_exists($class)){
                (new $class)->order($request);
            }
        }
    }

    public function notify(Request $request)
    {

    }

    public function return(Request $request)
    {
        $payment_id = $request->input('payment_id',1);
        $method = Method::where('id',$payment_id)->where('status',1)->first();
        if(!empty($method)){
            $class = '\Aphly\LaravelPayment\Controllers\Front\\'.ucfirst($method->name).'Controller';
            if (class_exists($class)) {
                (new $class)->return($request);
            }
        }
    }

    public function form(Request $request)
    {
        $res['title'] = 'Payment return';
        return $this->makeView('laravel-payment::front.payment.form',['res'=>$res]);
    }
}
