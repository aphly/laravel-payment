<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public $log;

    function __construct(){
        parent::__construct();
        $this->log = Log::channel('payment');
    }

    public function notify(Request $request)
    {
        $this->log->debug('payment_notify start '.$request->header('host'));
        $method = PaymentMethod::where('name',$request->method_name)->where('status',1)->firstOrError();
        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
        if (class_exists($class)) {
            (new $class)->notify($method->id);
        }
    }

    public function return(Request $request)
    {
        $this->log->debug('payment_return start');
        $method = PaymentMethod::where('name',$request->method_name)->where('status',1)->firstOrError();
        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
        if (class_exists($class)) {
            (new $class)->return($method->id);
        }
    }
//
//    public function show(Request $request)
//    {
//        $payment = Payment::where(['id'=>$request->query('payment_id')])->firstOrError();
//        $method = PaymentMethod::where('id',$payment->method_id)->where('status',1)->firstOrError();
//        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
//        if (class_exists($class)) {
//            (new $class)->show($payment);
//        }
//    }
//
//    public function refund(Request $request)
//    {
//        $payment = Payment::where(['id'=>$request->input('payment_id')])->firstOrError();
//        $method = PaymentMethod::where('id',$payment->method_id)->where('status',1)->firstOrError();
//        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
//        if (class_exists($class)) {
//            (new $class)->refund($payment);
//        }
//    }


}
