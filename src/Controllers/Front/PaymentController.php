<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\Method;
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

    public function refer()
    {
        $method_name = 'paypal';
//        if(isset($_SERVER['HTTP_REFERER'])) {
//            $method_name = 1;
//        }else{
//            $method_name = 0;
//        }
        return $method_name;
    }

    public function notify()
    {
        $this->log->debug('payment_notify start');
        $method_name = $this->refer();
        $method = Method::where('name',$method_name)->where('status',1)->first();
        if(!empty($method)){
            $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
            if (class_exists($class)) {
                (new $class)->notify($method->id);
            }
        }
    }

    public function return()
    {
        $this->log->debug('payment_return start');
        $method_name = $this->refer();
        $method = Method::where('name',$method_name)->where('status',1)->first();
        if(!empty($method)){
            $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
            if (class_exists($class)) {
                (new $class)->return($method->id);
            }
        }
    }

    public function show(Request $request)
    {
        $payment = Payment::where(['id'=>$request->query('payment_id')])->first();
        if(!empty($payment)){
            $method = Method::where('id',$payment->method_id)->where('status',1)->first();
            if(!empty($method)){
                $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($method->name);
                if (class_exists($class)) {
                    (new $class)->show($payment);
                }
            }
        }
    }




}
